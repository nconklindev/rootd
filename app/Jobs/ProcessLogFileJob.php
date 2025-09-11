<?php

namespace App\Jobs;

use App\Models\LogAnalysis;
use App\Services\LogAnalysisService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessLogFileJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public LogAnalysis $analysis
    ) {}

    public function handle(LogAnalysisService $logAnalysisService): void
    {
        $startTime = microtime(true);

        try {
            Log::info("Starting log analysis for file: {$this->analysis->original_filename}");

            // Update status to processing
            $this->analysis->update([
                'processing_status' => 'processing',
                'progress_percentage' => 0,
            ]);

            // Delegate the actual analysis to the service
            $logAnalysisService->analyzeFile($this->analysis);

            // Calculate processing duration with microsecond precision
            $endTime = microtime(true);
            $processingDuration = $endTime - $startTime;

            // Mark as completed
            $this->analysis->update([
                'processing_status' => 'completed',
                'progress_percentage' => 100,
                'processed_at' => now(),
                'processing_duration_seconds' => $processingDuration,
                'threats_detected' => $this->analysis->threatDetections()->count(),
            ]);

            Log::info("Completed log analysis for file: {$this->analysis->original_filename}");

        } catch (Exception $e) {
            Log::error('Failed to process log file: '.$e->getMessage(), [
                'analysis_id' => $this->analysis->id,
                'filename' => $this->analysis->original_filename,
                'error' => $e->getMessage(),
            ]);

            $this->analysis->update([
                'processing_status' => 'failed',
                'analysis_results' => ['error' => $e->getMessage()],
            ]);

            throw $e; // Re-throw for queue retry logic
        }
    }
}
