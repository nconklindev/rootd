<?php

use App\Services\ThreatDetectors\SqlInjectionDetector;

beforeEach(function () {
    $this->detector = new SqlInjectionDetector;
    $this->parsed = [
        'ip' => '192.168.1.1',
        'timestamp' => '25/Dec/2023:10:00:00 +0000',
        'status_code' => 200,
        'user_agent' => 'Mozilla/5.0',
        'raw_line' => 'test log line',
    ];
});

test('detects union select injection attempt', function () {
    $this->parsed['request'] = 'GET /index.php?id=1 UNION SELECT password FROM users';

    $threats = $this->detector->detect($this->parsed, 1);

    expect($threats)->toHaveCount(1);
    expect($threats[0]['threat_type'])->toBe('sql_injection');
    expect($threats[0]['severity'])->toBe('high');
    expect($threats[0]['description'])->toBe('UNION SELECT injection attempt');
    expect($threats[0]['confidence_score'])->toBe(85);
});

test('detects or 1=1 injection attempt', function () {
    $this->parsed['request'] = "GET /login.php?user=admin' OR 1=1--";

    $threats = $this->detector->detect($this->parsed, 1);

    expect($threats)->toHaveCount(1);
    expect($threats[0]['description'])->toBe('OR 1=1 injection attempt');
});

test('detects drop table attempt', function () {
    $this->parsed['request'] = 'POST /admin.php DROP TABLE users';

    $threats = $this->detector->detect($this->parsed, 1);

    expect($threats)->toHaveCount(1);
    expect($threats[0]['description'])->toBe('DROP TABLE attempt');
});

test('detects xss script injection', function () {
    $this->parsed['request'] = 'GET /search.php?q=<script>alert("xss")</script>';

    $threats = $this->detector->detect($this->parsed, 1);

    expect($threats)->toHaveCount(1);
    expect($threats[0]['description'])->toBe('XSS script injection');
});

test('returns empty array for safe requests', function () {
    $this->parsed['request'] = 'GET /normal-page.html';

    $threats = $this->detector->detect($this->parsed, 1);

    expect($threats)->toHaveCount(0);
});

test('includes correct metadata in threat detection', function () {
    $this->parsed['request'] = 'GET /test.php?id=1 UNION SELECT *';

    $threats = $this->detector->detect($this->parsed, 5);

    expect($threats[0]['line_number'])->toBe(5);
    expect($threats[0]['source_ip'])->toBe('192.168.1.1');
    expect($threats[0]['metadata']['request_uri'])->toBe('GET /test.php?id=1 UNION SELECT *');
    expect($threats[0]['metadata']['status_code'])->toBe(200);
    expect($threats[0]['metadata']['user_agent'])->toBe('Mozilla/5.0');
});
