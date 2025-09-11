<?php

use App\Services\ThreatDetectors\BruteForceDetector;
use Carbon\Carbon;

beforeEach(function () {
    $this->detector = new BruteForceDetector;
    $this->failedAttempts = []; // State array passed by reference
    $this->baseParsed = [
        'ip' => '192.168.1.100',
        'timestamp' => '25/Dec/2023:10:00:00 +0000',
        'status_code' => 401,
        'user_agent' => 'Mozilla/5.0',
        'raw_line' => 'test log line',
    ];
});

test('ignores successful authentication attempts', function () {
    $parsed = array_merge($this->baseParsed, [
        'request' => 'POST /login',
        'status_code' => 200,  // Success
    ]);

    $threats = $this->detector->detect($parsed, 1, $this->failedAttempts);

    expect($threats)->toHaveCount(0);
});

test('ignores non-authentication endpoints with failed status', function () {
    $parsed = array_merge($this->baseParsed, [
        'request' => 'GET /images/logo.png',
        'status_code' => 404,
    ]);

    $threats = $this->detector->detect($parsed, 1, $this->failedAttempts);

    expect($threats)->toHaveCount(0);
});

test('detects single failed authentication attempt but no threat yet', function () {
    $parsed = array_merge($this->baseParsed, [
        'request' => 'POST /login',
    ]);

    $threats = $this->detector->detect($parsed, 1, $this->failedAttempts);

    expect($threats)->toHaveCount(0); // Single attempt is not enough
});

test('detects sustained brute force attack', function () {
    $parsed = array_merge($this->baseParsed, [
        'request' => 'POST /login',
    ]);

    // Simulate 5 failed attempts (threshold) over time
    $threats = [];
    for ($i = 1; $i <= 5; $i++) {
        $parsed['timestamp'] = "25/Dec/2023:10:0{$i}:00 +0000";
        $threats = $this->detector->detect($parsed, $i, $this->failedAttempts);
    }

    expect($threats)->toHaveCount(1);
    expect($threats[0]['threat_type'])->toBe('brute_force');
    expect($threats[0]['severity'])->toBe('high');
    expect($threats[0]['description'])->toContain('Sustained brute force attack');
    expect($threats[0]['confidence_score'])->toBe(90);
    expect($threats[0]['metadata']['attack_pattern'])->toBe('sustained_brute_force');
    expect($threats[0]['metadata']['total_attempts'])->toBe(5);
});

test('detects rapid fire brute force attack', function () {
    $parsed = array_merge($this->baseParsed, [
        'request' => 'POST /admin',
    ]);

    // Simulate 3 rapid attempts within 60 seconds
    $baseTime = Carbon::createFromFormat('d/M/Y:H:i:s', '25/Dec/2023:10:00:00');

    $threats = [];
    for ($i = 1; $i <= 3; $i++) {
        $time = $baseTime->copy()->addSeconds($i * 10); // 10 seconds apart
        $parsed['timestamp'] = $time->format('d/M/Y:H:i:s').' +0000';
        $threats = $this->detector->detect($parsed, $i, $this->failedAttempts);
    }

    expect($threats)->toHaveCount(1);
    expect($threats[0]['threat_type'])->toBe('brute_force');
    expect($threats[0]['severity'])->toBe('medium');
    expect($threats[0]['description'])->toContain('Rapid authentication attempts');
    expect($threats[0]['confidence_score'])->toBe(75);
    expect($threats[0]['metadata']['attack_pattern'])->toBe('rapid_fire_brute_force');
    expect($threats[0]['metadata']['rapid_attempts'])->toBe(3);
});

test('tracks different IPs separately', function () {
    $failedAttempts = []; // Fresh state for this test

    $parsed1 = array_merge($this->baseParsed, [
        'ip' => '192.168.1.100',
        'request' => 'POST /login',
    ]);

    $parsed2 = array_merge($this->baseParsed, [
        'ip' => '192.168.1.200',
        'request' => 'POST /login',
    ]);

    // Multiple attempts from first IP
    $threats1 = [];
    for ($i = 1; $i <= 5; $i++) {
        $parsed1['timestamp'] = "25/Dec/2023:10:0{$i}:00 +0000";
        $threats1 = $this->detector->detect($parsed1, $i, $failedAttempts);
    }

    // Single attempt from second IP
    $threats2 = $this->detector->detect($parsed2, 6, $failedAttempts);

    expect($threats1)->toHaveCount(1); // First IP triggers threat
    expect($threats2)->toHaveCount(0); // Second IP doesn't
});

test('recognizes various authentication endpoints', function () {
    $authEndpoints = [
        '/login', '/admin', '/wp-admin', '/wp-login',
        '/auth', '/signin', '/dashboard', '/control', '/panel',
    ];

    foreach ($authEndpoints as $endpoint) {
        $failedAttempts = []; // Fresh state for each test
        $parsed = array_merge($this->baseParsed, [
            'request' => "POST {$endpoint}",
            'ip' => '192.168.1.'.(100 + array_search($endpoint, $authEndpoints)),
        ]);

        // Generate enough attempts to trigger detection
        $threats = [];
        for ($i = 1; $i <= 5; $i++) {
            $parsed['timestamp'] = "25/Dec/2023:10:0{$i}:00 +0000";
            $threats = $this->detector->detect($parsed, $i, $failedAttempts);
        }

        expect($threats)->toHaveCount(1, "Failed to detect brute force for endpoint: {$endpoint}");
    }
});

test('cleans up old attempts outside time window', function () {
    $parsed = array_merge($this->baseParsed, [
        'request' => 'POST /login',
    ]);

    // First attempt 15 minutes ago (outside 10-minute window)
    $oldTime = Carbon::createFromFormat('d/M/Y:H:i:s', '25/Dec/2023:09:45:00');
    $parsed['timestamp'] = $oldTime->format('d/M/Y:H:i:s').' +0000';
    $this->detector->detect($parsed, 1, $this->failedAttempts);

    // Current attempts within window
    $currentTime = Carbon::createFromFormat('d/M/Y:H:i:s', '25/Dec/2023:10:00:00');
    $threats = [];
    for ($i = 2; $i <= 5; $i++) {
        $time = $currentTime->copy()->addMinutes($i);
        $parsed['timestamp'] = $time->format('d/M/Y:H:i:s').' +0000';
        $threats = $this->detector->detect($parsed, $i, $this->failedAttempts);
    }

    // Should only count recent attempts (4), not the old one
    expect($threats)->toHaveCount(0); // 4 attempts < 5 threshold
});

test('handles malformed timestamps gracefully', function () {
    $parsed = array_merge($this->baseParsed, [
        'request' => 'POST /login',
        'timestamp' => 'invalid-timestamp-format',
    ]);

    // Should not throw exception and should still work
    $threats = $this->detector->detect($parsed, 1, $this->failedAttempts);

    expect($threats)->toHaveCount(0); // Single attempt, no pattern yet
});
