<?php

namespace App\Jobs;

use App\Models\LogAnalysis;
use App\Models\ThreatDetection;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessLogFileJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public LogAnalysis $analysis
    )
    {
    }

    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            Log::info("Starting log analysis for file: {$this->analysis->original_filename}");

            // Update status to processing
            $this->analysis->update([
                'processing_status' => 'processing',
                'progress_percentage' => 0
            ]);

            // Get the file path and verify it exists
            $filePath = storage_path('app/private/' . $this->analysis->file_path);
            if (!file_exists($filePath)) {
                throw new Exception("Log file not found: $filePath");
            }

            // Detect the log format and create the parser
            $logFormat = $this->detectLogFormat($filePath);
            $this->analysis->update(['log_format' => $logFormat]);

            // Process the file line by line
            $this->processLogFile($filePath);

            // Calculate processing duration with microsecond precision
            $endTime = microtime(true);
            $processingDuration = $endTime - $startTime;

            // Mark as completed
            $this->analysis->update([
                'processing_status' => 'completed',
                'progress_percentage' => 100,
                'processed_at' => now(),
                'processing_duration_seconds' => $processingDuration,
                'threats_detected' => $this->analysis->threatDetections()->count()
            ]);

            Log::info("Completed log analysis for file: {$this->analysis->original_filename}");

        } catch (Exception $e) {
            Log::error("Failed to process log file: " . $e->getMessage(), [
                'analysis_id' => $this->analysis->id,
                'filename' => $this->analysis->original_filename,
                'error' => $e->getMessage()
            ]);

            $this->analysis->update([
                'processing_status' => 'failed',
                'analysis_results' => ['error' => $e->getMessage()]
            ]);

            throw $e; // Re-throw for queue retry logic
        }
    }

    private function detectLogFormat(string $filePath): string
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

    private function processLogFile(string $filePath): void
    {
        $handle = fopen($filePath, 'r');
        $lineNumber = 0;
        $batchSize = 100;
        $batch = [];

        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            $batch[] = ['line' => trim($line), 'line_number' => $lineNumber];

            // Process in batches for memory efficiency
            if (count($batch) >= $batchSize) {
                $this->processBatch($batch);
                $batch = [];

                // Update progress
                $this->updateProgress($lineNumber);
            }
        }

        // Process the final batch
        if (!empty($batch)) {
            $this->processBatch($batch);
        }

        fclose($handle);

        // Update final totals
        $this->analysis->update([
            'total_entries' => $lineNumber,
            'entries_processed' => $lineNumber
        ]);
    }

    private function processBatch(array $batch): void
    {
        foreach ($batch as $entry) {
            $threats = $this->analyzeLogEntry($entry['line'], $entry['line_number']);

            foreach ($threats as $threat) {
                $this->analysis->threatDetections()->create($threat);
            }
        }
    }

    private function analyzeLogEntry(string $logLine, int $lineNumber): array
    {
        $threats = [];

        // Parse nginx/apache log format
        $parsed = $this->parseNginxLog($logLine);
        if (!$parsed) {
            return $threats;
        }

        // Run various threat detectors
        $threats = array_merge($threats, $this->detectSqlInjection($parsed, $lineNumber));
        $threats = array_merge($threats, $this->detectDirectoryTraversal($parsed, $lineNumber));
        $threats = array_merge($threats, $this->detectBruteForce($parsed, $lineNumber));
        $threats = array_merge($threats, $this->detectScannerActivity($parsed, $lineNumber));
        return array_merge($threats, $this->detectErrorPatterns($parsed, $lineNumber));
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
                'status_code' => (int)$matches[4],
                'size' => $matches[5],
                'referer' => $matches[6],
                'user_agent' => $matches[7],
                'raw_line' => $logLine
            ];
        }

        return null;
    }

    private function detectSqlInjection(array $parsed, int $lineNumber): array
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
                        'user_agent' => $parsed['user_agent']
                    ]
                ];
            }
        }

        return $threats;
    }

    private function detectDirectoryTraversal(array $parsed, int $lineNumber): array
    {
        $threats = [];
        $request = $parsed['request'];

        $traversalPatterns = [
            '/\.\.\//i' => 'Directory traversal attempt (../)',
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
                        'status_code' => $parsed['status_code']
                    ]
                ];
            }
        }

        return $threats;
    }

    private function detectBruteForce(array $parsed, int $lineNumber): array
    {
        $threats = [];

        // Look for multiple failed login attempts (4xx status codes)
        if ($parsed['status_code'] >= 400 && $parsed['status_code'] < 500) {
            // This is a simplified check - in real implementation, you'd track IPs over time
            if (stripos($parsed['request'], 'login') !== false ||
                stripos($parsed['request'], 'admin') !== false ||
                stripos($parsed['request'], 'wp-admin') !== false) {

                $threats[] = [
                    'threat_type' => 'brute_force',
                    'severity' => 'medium',
                    'description' => 'Failed authentication attempt',
                    'line_number' => $lineNumber,
                    'raw_log_entry' => $parsed['raw_line'],
                    'source_ip' => $parsed['ip'],
                    'timestamp_detected' => now(),
                    'confidence_score' => 60,
                    'metadata' => [
                        'request_uri' => $parsed['request'],
                        'status_code' => $parsed['status_code'],
                        'failed_auth' => true
                    ]
                ];
            }
        }

        return $threats;
    }

    private function detectScannerActivity(array $parsed, int $lineNumber): array
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
                    'description' => $description . ' detected',
                    'line_number' => $lineNumber,
                    'raw_log_entry' => $parsed['raw_line'],
                    'source_ip' => $parsed['ip'],
                    'timestamp_detected' => now(),
                    'confidence_score' => 90,
                    'metadata' => [
                        'user_agent' => $parsed['user_agent'],
                        'scanner_type' => $description
                    ]
                ];
            }
        }

        return $threats;
    }

    private function detectErrorPatterns(array $parsed, int $lineNumber): array
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
                    'error_type' => 'server_error'
                ]
            ];
        }

        return $threats;
    }

    private function updateProgress(int $lineNumber): void
    {
        // Update progress every 1000 lines
        if ($lineNumber % 1000 === 0) {
            // Estimate progress based on file size (rough approximation)
            $progress = min(90, ($lineNumber / 100)); // Cap at 90% until completion

            $this->analysis->update([
                'progress_percentage' => $progress,
                'entries_processed' => $lineNumber
            ]);
        }
    }
}
