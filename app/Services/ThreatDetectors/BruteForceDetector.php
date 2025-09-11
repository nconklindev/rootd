<?php

namespace App\Services\ThreatDetectors;

use Carbon\Carbon;

class BruteForceDetector
{
    private int $maxAttemptsThreshold = 5;

    private int $timeWindowMinutes = 10;

    private int $rapidAttemptsThreshold = 3;

    private int $rapidTimeWindowSeconds = 60;

    public function detect(array $parsed, int $lineNumber, array &$failedAttempts): array
    {
        $threats = [];
        $ip = $parsed['ip'];
        $timestamp = $this->parseTimestamp($parsed['timestamp']);

        // Check if this is a failed authentication attempt
        if ($this->isFailedAuthAttempt($parsed)) {
            $this->recordFailedAttempt($ip, $timestamp, $parsed, $lineNumber, $failedAttempts);

            // Clean up old attempts outside our time window
            $this->cleanupOldAttempts($ip, $timestamp, $failedAttempts);

            // Check for brute force patterns
            $threats = array_merge($threats, $this->analyzeFailurePatterns($ip, $timestamp, $parsed, $lineNumber, $failedAttempts));
        }

        return $threats;
    }

    private function parseTimestamp(string $timestamp): Carbon
    {
        // Handle different timestamp formats
        try {
            // Apache/Nginx format: 25/Dec/2023:10:00:00 +0000
            if (preg_match('/(\d{2}\/\w{3}\/\d{4}:\d{2}:\d{2}:\d{2})/', $timestamp, $matches)) {
                return Carbon::createFromFormat('d/M/Y:H:i:s', $matches[1]);
            }

            // ISO format fallback
            return Carbon::parse($timestamp);
        } catch (\Exception $e) {
            // Fallback to current time if parsing fails
            return Carbon::now();
        }
    }

    private function isFailedAuthAttempt(array $parsed): bool
    {
        // Failed status codes (4xx range)
        if ($parsed['status_code'] < 400 || $parsed['status_code'] >= 500) {
            return false;
        }

        // Check for authentication-related endpoints
        $authPatterns = [
            '/login',
            '/admin',
            '/wp-admin',
            '/wp-login',
            '/auth',
            '/signin',
            '/dashboard',
            '/control',
            '/panel',
        ];

        $request = strtolower($parsed['request']);
        foreach ($authPatterns as $pattern) {
            if (stripos($request, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    private function recordFailedAttempt(string $ip, Carbon $timestamp, array $parsed, int $lineNumber, array &$failedAttempts): void
    {
        if (!isset($failedAttempts[$ip])) {
            $failedAttempts[$ip] = [];
        }

        $failedAttempts[$ip][] = [
            'timestamp' => $timestamp,
            'request' => $parsed['request'],
            'status_code' => $parsed['status_code'],
            'line_number' => $lineNumber,
            'raw_line' => $parsed['raw_line'],
        ];
    }

    private function cleanupOldAttempts(string $ip, Carbon $currentTime, array &$failedAttempts): void
    {
        if (!isset($failedAttempts[$ip])) {
            return;
        }

        $cutoff = $currentTime->copy()->subMinutes($this->timeWindowMinutes);

        $failedAttempts[$ip] = array_filter(
            $failedAttempts[$ip],
            fn($attempt) => $attempt['timestamp']->greaterThan($cutoff)
        );

        // Remove IP entirely if no recent attempts
        if (empty($failedAttempts[$ip])) {
            unset($failedAttempts[$ip]);
        }
    }

    private function analyzeFailurePatterns(string $ip, Carbon $timestamp, array $parsed, int $lineNumber, array &$failedAttempts): array
    {
        $threats = [];
        $attempts = $failedAttempts[$ip] ?? [];
        $attemptCount = count($attempts);

        if ($attemptCount < 2) {
            return $threats; // Need at least 2 attempts for pattern analysis
        }

        // Initialize threat tracking for this IP if not exists
        if (!isset($failedAttempts[$ip . '_threats_reported'])) {
            $failedAttempts[$ip . '_threats_reported'] = [];
        }

        // Pattern 1: High volume brute force (many attempts over longer period)
        if ($attemptCount >= $this->maxAttemptsThreshold &&
            !in_array('sustained_brute_force', $failedAttempts[$ip . '_threats_reported'])) {

            $threats[] = [
                'threat_type' => 'brute_force',
                'severity' => 'high',
                'description' => "Sustained brute force attack detected: {$attemptCount} failed authentication attempts",
                'line_number' => $lineNumber,
                'raw_log_entry' => $parsed['raw_line'],
                'source_ip' => $ip,
                'timestamp_detected' => now(),
                'confidence_score' => 90,
                'metadata' => [
                    'total_attempts' => $attemptCount,
                    'time_window_minutes' => $this->timeWindowMinutes,
                    'attack_pattern' => 'sustained_brute_force',
                    'first_attempt' => $attempts[0]['timestamp']->toISOString(),
                    'latest_attempt' => $timestamp->toISOString(),
                ],
            ];

            // Mark this threat pattern as reported
            $failedAttempts[$ip . '_threats_reported'][] = 'sustained_brute_force';
        }

        // Pattern 2: Rapid-fire attempts (many attempts in short time)
        $rapidCutoff = $timestamp->copy()->subSeconds($this->rapidTimeWindowSeconds);
        $rapidAttempts = array_filter(
            $attempts,
            fn($attempt) => $attempt['timestamp']->greaterThan($rapidCutoff)
        );

        if (count($rapidAttempts) >= $this->rapidAttemptsThreshold &&
            !in_array('rapid_fire_brute_force', $failedAttempts[$ip . '_threats_reported'])) {

            $threats[] = [
                'threat_type' => 'brute_force',
                'severity' => 'medium',
                'description' => 'Rapid authentication attempts detected: ' . count($rapidAttempts) . " attempts in {$this->rapidTimeWindowSeconds} seconds",
                'line_number' => $lineNumber,
                'raw_log_entry' => $parsed['raw_line'],
                'source_ip' => $ip,
                'timestamp_detected' => now(),
                'confidence_score' => 75,
                'metadata' => [
                    'rapid_attempts' => count($rapidAttempts),
                    'time_window_seconds' => $this->rapidTimeWindowSeconds,
                    'attack_pattern' => 'rapid_fire_brute_force',
                    'request_uri' => $parsed['request'],
                    'status_code' => $parsed['status_code'],
                ],
            ];

            // Mark this threat pattern as reported
            $failedAttempts[$ip . '_threats_reported'][] = 'rapid_fire_brute_force';
        }

        return $threats;
    }
}
