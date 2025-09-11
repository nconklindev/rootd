<?php

namespace App\Services;

class LogParserService
{
    public function parse(string $logLine, string $format): ?array
    {
        return match ($format) {
            'Apache/Nginx Combined' => $this->parseNginxLog($logLine),
            'JSON' => $this->parseJsonLog($logLine),
            'ISO Timestamp' => $this->parseIsoLog($logLine),
            default => null
        };
    }

    private function parseNginxLog(string $logLine): ?array
    {
        // Nginx/Apache Combined Log Format
        $pattern = '/^(\S+) \S+ \S+ \[([^]]+)] "([^"]*)" (\d+) (\S+) "([^"]*)" "([^"]*)"$/';

        if (preg_match($pattern, $logLine, $matches)) {
            return [
                'ip' => $matches[1],
                'timestamp' => $matches[2],
                'request' => $matches[3],
                'status_code' => (int) $matches[4],
                'size' => $matches[5],
                'referer' => $matches[6],
                'user_agent' => $matches[7],
                'raw_line' => $logLine,
            ];
        }

        return null;
    }

    private function parseJsonLog(string $logLine): ?array
    {
        $decoded = json_decode(trim($logLine), true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return array_merge($decoded, ['raw_line' => $logLine]);
        }

        return null;
    }

    private function parseIsoLog(string $logLine): ?array
    {
        // Basic ISO timestamp log parsing - can be extended
        if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2})\s+(.*)/', $logLine, $matches)) {
            return [
                'timestamp' => $matches[1],
                'message' => $matches[2],
                'raw_line' => $logLine,
            ];
        }

        return null;
    }
}
