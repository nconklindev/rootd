<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Http\Requests\LogUploadRequest;
use App\Jobs\ProcessLogFileJob;
use App\Models\LogAnalysis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LogAnalysisController extends Controller
{
    public function index()
    {
        // Get real analysis data for the current user
        $recentAnalyses = auth()->user()
            ->logAnalyses()
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($analysis) {
                return [
                    'id' => $analysis->id,
                    'filename' => $analysis->original_filename,
                    'size' => $analysis->formatted_file_size,
                    'format' => $analysis->log_format ?? 'Detecting...',
                    'entries' => $analysis->total_entries,
                    'threats_detected' => $analysis->threats_detected,
                    'analyzed_at' => $analysis->processed_at?->toISOString() ?? $analysis->created_at->toISOString(),
                    'status' => $analysis->processing_status
                ];
            });

        $supportedFormats = [
            [
                'name' => 'Apache Combined Log',
                'description' => 'Standard Apache web server access logs',
                'extensions' => ['.log', '.txt'],
                'sample' => '127.0.0.1 - - [10/Oct/2000:13:55:36 -0700] "GET /apache_pb.gif HTTP/1.0" 200 2326'
            ],
            [
                'name' => 'Nginx Access/Error Log',
                'description' => 'Nginx web server access logs',
                'extensions' => ['.log', '.txt'],
                'sample' => '192.168.1.1 - - [25/Dec/2002:17:15:27 +0000] "GET / HTTP/1.1" 200 123'
            ],
            [
                'name' => 'Windows Event Log',
                'description' => 'Windows system and security event logs',
                'extensions' => ['.evtx', '.evt', '.txt'],
                'sample' => 'Event ID: 4624, Logon Type: 3, Account Name: user@domain.com'
            ],
            [
                'name' => 'Syslog',
                'description' => 'Standard Unix/Linux system logs',
                'extensions' => ['.log', '.txt'],
                'sample' => 'Dec 10 06:30:00 host kernel: [12345.678901] USB disconnect'
            ],
            [
                'name' => 'JSON Logs',
                'description' => 'Structured JSON format logs',
                'extensions' => ['.json', '.jsonl'],
                'sample' => '{"timestamp":"2023-01-01T12:00:00Z","level":"INFO","message":"Request processed"}'
            ]
        ];

        $analysisStats = [
            'total_logs_analyzed' => auth()->user()->logAnalyses()->count(),
            'total_entries_processed' => auth()->user()->logAnalyses()->sum('total_entries'),
            'threats_detected_total' => auth()->user()->logAnalyses()->sum('threats_detected'),
            'most_common_threat' => $this->getMostCommonThreatType(),
            'avg_processing_time' => $this->getAverageProcessingTime()
        ];

        return Inertia::render('Tools/Logs/Index', [
            'recentAnalyses' => $recentAnalyses,
            'supportedFormats' => $supportedFormats,
            'analysisStats' => $analysisStats
        ]);
    }

    private function getMostCommonThreatType(): string
    {
        $mostCommon = auth()->user()
            ->logAnalyses()
            ->join('threat_detections', 'log_analyses.id', '=', 'threat_detections.log_analysis_id')
            ->selectRaw('threat_type, count(*) as count')
            ->groupBy('threat_type')
            ->orderBy('count', 'desc')
            ->first();

        if (!$mostCommon) {
            return 'None detected';
        }

        // Convert snake_case to Title Case
        return Str::of($mostCommon->threat_type)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    private function getAverageProcessingTime(): string
    {
        $analyses = auth()->user()
            ->logAnalyses()
            ->whereNotNull('processing_duration_seconds')
            ->get();

        if ($analyses->isEmpty()) {
            return 'No data';
        }

        $avgSeconds = $analyses->avg('processing_duration_seconds');

        if ($avgSeconds < 1) {
            return round($avgSeconds * 1000) . ' ms';
        } elseif ($avgSeconds < 60) {
            return round($avgSeconds, 2) . ' seconds';
        } elseif ($avgSeconds < 3600) {
            return round($avgSeconds / 60, 1) . ' minutes';
        } else {
            return round($avgSeconds / 3600, 1) . ' hours';
        }
    }

    public function parser()
    {
        return Inertia::render('Tools/Logs/Parser');
    }

    public function upload(LogUploadRequest $request)
    {
        $uploadedCount = 0;

        foreach ($request->file('files') as $file) {
            // Store file securely in the log-analysis disk
            $path = $file->store('logs/' . auth()->id(), 'local');

            // Create analysis record
            $analysis = auth()->user()->logAnalyses()->create([
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'file_hash' => hash_file('sha256', $file->getPathname()),
                'processing_status' => 'pending'
            ]);

            // Queue for background processing
            ProcessLogFileJob::dispatch($analysis);

            $uploadedCount++;
        }

        return redirect()->route('tools.logs.index')
            ->with('success', "Successfully uploaded {$uploadedCount} file(s) for analysis. Processing will begin shortly.");
    }

    public function getProgress(LogAnalysis $analysis): JsonResponse
    {
        $this->authorize('view', $analysis);

        return response()->json([
            'id' => $analysis->id,
            'status' => $analysis->processing_status,
            'progress_percentage' => $analysis->progress_percentage,
            'entries_processed' => $analysis->entries_processed,
            'total_entries' => $analysis->total_entries,
            'threats_found' => $analysis->threatDetections()->count(),
            'processed_at' => $analysis->processed_at?->toISOString()
        ]);
    }

    public function show(LogAnalysis $analysis)
    {
        $this->authorize('view', $analysis);

        $analysis->load(['threatDetections' => function ($query) {
            $query->orderBy('severity', 'desc')->orderBy('created_at', 'desc');
        }]);

        return Inertia::render('Tools/Logs/Show', [
            'analysis' => $analysis,
            'threatsSummary' => [
                'by_severity' => $analysis->threatDetections()
                    ->selectRaw('severity, count(*) as count')
                    ->groupBy('severity')
                    ->get(),
                'by_type' => $analysis->threatDetections()
                    ->selectRaw('threat_type, count(*) as count')
                    ->groupBy('threat_type')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
            ]
        ]);
    }

    public function destroy(LogAnalysis $analysis): JsonResponse
    {
        $this->authorize('delete', $analysis);

        // Delete the file from storage
        if (Storage::exists($analysis->file_path)) {
            Storage::delete($analysis->file_path);
        }

        // Delete the analysis record (cascade will handle threat detections)
        $analysis->delete();

        return response()->json([
            'message' => 'Analysis deleted successfully'
        ]);
    }
}
