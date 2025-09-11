<?php

namespace App\Services\ThreatDetectors;

class SqlInjectionDetector
{
    public function detect(array $parsed, int $lineNumber): array
    {
        $threats = [];
        $request = strtolower($parsed['request']);

        $sqlPatterns = [
            '/union\s+select/i' => 'UNION SELECT injection attempt',
            '/\'\s*or\s*1\s*=\s*1/i' => 'OR 1=1 injection attempt',
            '/drop\s+table/i' => 'DROP TABLE attempt',
            '/insert\s+into/i' => 'INSERT injection attempt',
            '/delete\s+from/i' => 'DELETE injection attempt',
            '/exec\s*\(/i' => 'Command execution attempt',
            '/script\s*>/i' => 'XSS script injection',
        ];

        foreach ($sqlPatterns as $pattern => $description) {
            if (preg_match($pattern, $request)) {
                $threats[] = [
                    'threat_type' => 'sql_injection',
                    'severity' => 'high',
                    'description' => $description,
                    'line_number' => $lineNumber,
                    'raw_log_entry' => $parsed['raw_line'],
                    'source_ip' => $parsed['ip'],
                    'timestamp_detected' => now(),
                    'confidence_score' => 85,
                    'metadata' => [
                        'request_uri' => $parsed['request'],
                        'status_code' => $parsed['status_code'],
                        'user_agent' => $parsed['user_agent'],
                    ],
                ];
            }
        }

        return $threats;
    }
}
