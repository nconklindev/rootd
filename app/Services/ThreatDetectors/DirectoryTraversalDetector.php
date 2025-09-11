<?php

namespace App\Services\ThreatDetectors;

class DirectoryTraversalDetector
{
    public function detect(array $parsed, int $lineNumber): array
    {
        $threats = [];
        $request = $parsed['request'];

        $traversalPatterns = [
            '/\.\.\//' => 'Directory traversal attempt (../)',
            '/\.\.\\\\/i' => 'Directory traversal attempt (..\\)',
            '/%2e%2e%2f/i' => 'URL encoded directory traversal',
            '/etc\/passwd/i' => 'Attempt to access /etc/passwd',
            '/windows\/system32/i' => 'Attempt to access Windows system files',
        ];

        foreach ($traversalPatterns as $pattern => $description) {
            if (preg_match($pattern, $request)) {
                $threats[] = [
                    'threat_type' => 'directory_traversal',
                    'severity' => 'medium',
                    'description' => $description,
                    'line_number' => $lineNumber,
                    'raw_log_entry' => $parsed['raw_line'],
                    'source_ip' => $parsed['ip'],
                    'timestamp_detected' => now(),
                    'confidence_score' => 75,
                    'metadata' => [
                        'request_uri' => $parsed['request'],
                        'status_code' => $parsed['status_code'],
                    ],
                ];
            }
        }

        return $threats;
    }
}
