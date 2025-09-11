<?php

namespace App\Services;

use App\Models\LogAnalysis;
use Exception;
use Illuminate\Support\Facades\Log;

class LogAnalysisService
{
    public function __construct(
        private LogFormatDetectionService $formatDetectionService,
        private LogParserService $logParserService,
        private ThreatDetectionService $threatDetectionService
    ) {}

    public function analyzeFile(LogAnalysis $analysis): void
    {
        // Get the file path and verify it exists
        $filePath = storage_path('app/private/'.$analysis->file_path);
        if (! file_exists($filePath)) {
            throw new Exception("Log file not found: $filePath");
        }

        // Detect the log format and create the parser
        $logFormat = $this->formatDetectionService->detectFormat($filePath);
        $analysis->update(['log_format' => $logFormat]);

        // Process the file line by line
        $this->processLogFile($filePath, $analysis);
    }

    private function processLogFile(string $filePath, LogAnalysis $analysis): void
    {
        $handle = fopen($filePath, 'r');
        $lineNumber = 0;
        $batchSize = 100;
        $batch = [];

        // Initialize state for brute force tracking across entire analysis
        $bruteForceState = [];

        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            $batch[] = ['line' => trim($line), 'line_number' => $lineNumber];

            // Process in batches for memory efficiency
            if (count($batch) >= $batchSize) {
                $this->processBatch($batch, $analysis, $bruteForceState);
                $batch = [];

                // Update progress
                $this->updateProgress($lineNumber, $analysis);
            }
        }

        // Process the final batch
        if (! empty($batch)) {
            $this->processBatch($batch, $analysis, $bruteForceState);
        }

        fclose($handle);

        // Update final totals
        $analysis->update([
            'total_entries' => $lineNumber,
            'entries_processed' => $lineNumber,
        ]);
    }

    private function processBatch(array $batch, LogAnalysis $analysis, array &$bruteForceState): void
    {
        foreach ($batch as $entry) {
            $threats = $this->analyzeLogEntry($entry['line'], $entry['line_number'], $analysis, $bruteForceState);

            foreach ($threats as $threat) {
                $analysis->threatDetections()->create($threat);
            }
        }
    }

    private function analyzeLogEntry(string $logLine, int $lineNumber, LogAnalysis $analysis, array &$bruteForceState): array
    {
        // Parse the log line based on the detected format
        $parsed = $this->logParserService->parse($logLine, $analysis->log_format);
        if (! $parsed) {
            return [];
        }

        // Run threat detection with persistent brute force state
        return $this->threatDetectionService->detectThreats($parsed, $lineNumber, $bruteForceState);
    }

    private function updateProgress(int $lineNumber, LogAnalysis $analysis): void
    {
        // Update progress every 1000 lines
        if ($lineNumber % 1000 === 0) {
            // Estimate progress based on file size (rough approximation)
            $progress = min(90, ($lineNumber / 100)); // Cap at 90% until completion

            $analysis->update([
                'progress_percentage' => $progress,
                'entries_processed' => $lineNumber,
            ]);
        }
    }
}
