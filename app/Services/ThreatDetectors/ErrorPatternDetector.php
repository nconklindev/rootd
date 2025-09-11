<?php

namespace App\Services\ThreatDetectors;

class ErrorPatternDetector
{
    public function detect(array $parsed, int $lineNumber): array
    {
        $threats = [];

        // High error rates might indicate attacks
        if ($parsed['status_code'] >= 500) {
            $threats[] = [
                'threat_type' => 'server_error',
                'severity' => 'low',
                'description' => 'Server error response (5xx)',
                'line_number' => $lineNumber,
                'raw_log_entry' => $parsed['raw_line'],
                'source_ip' => $parsed['ip'],
                'timestamp_detected' => now(),
                'confidence_score' => 40,
                'metadata' => [
                    'status_code' => $parsed['status_code'],
                    'error_type' => 'server_error',
                ],
            ];
        }

        return $threats;
    }
}
