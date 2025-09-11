<?php

namespace App\Services;

class LogFormatDetectionService
{
    public function detectFormat(string $filePath): string
    {
        $handle = fopen($filePath, 'r');
        $firstLine = fgets($handle);
        fclose($handle);

        // Basic format detection based on patterns
        if (preg_match('/^\d+\.\d+\.\d+\.\d+.*\[.*].*"(GET|POST|PUT|DELETE)/', $firstLine)) {
            return 'Apache/Nginx Combined';
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $firstLine)) {
            return 'ISO Timestamp';
        } elseif (str_starts_with($firstLine, '{"')) {
            return 'JSON';
        }

        return 'Unknown';
    }
}
