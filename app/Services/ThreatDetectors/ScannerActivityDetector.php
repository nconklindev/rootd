<?php

namespace App\Services\ThreatDetectors;

class ScannerActivityDetector
{
    public function detect(array $parsed, int $lineNumber): array
    {
        $threats = [];
        $request = strtolower($parsed['request']);
        $userAgent = strtolower($parsed['user_agent']);

        // Common scanner signatures
        $scannerPatterns = [
            '/nikto/i' => 'Nikto vulnerability scanner',
            '/nmap/i' => 'Nmap port scanner',
            '/sqlmap/i' => 'SQLMap injection tool',
            '/masscan/i' => 'Masscan port scanner',
            '/zap/i' => 'OWASP ZAP scanner',
            '/burp/i' => 'Burp Suite scanner',
        ];

        foreach ($scannerPatterns as $pattern => $description) {
            if (preg_match($pattern, $userAgent)) {
                $threats[] = [
                    'threat_type' => 'scanner_activity',
                    'severity' => 'medium',
                    'description' => $description.' detected',
                    'line_number' => $lineNumber,
                    'raw_log_entry' => $parsed['raw_line'],
                    'source_ip' => $parsed['ip'],
                    'timestamp_detected' => now(),
                    'confidence_score' => 90,
                    'metadata' => [
                        'user_agent' => $parsed['user_agent'],
                        'scanner_type' => $description,
                    ],
                ];
            }
        }

        return $threats;
    }
}
