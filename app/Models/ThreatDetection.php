<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThreatDetection extends Model
{
    protected $fillable = [
        'log_analysis_id',
        'threat_type',
        'severity',
        'description',
        'line_number',
        'raw_log_entry',
        'source_ip',
        'timestamp_detected',
        'confidence_score',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'timestamp_detected' => 'datetime',
            'line_number' => 'integer',
            'confidence_score' => 'integer',
        ];
    }

    public function logAnalysis(): BelongsTo
    {
        return $this->belongsTo(LogAnalysis::class);
    }

    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
            'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300',
            'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-300',
            'low' => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-950 dark:text-gray-300',
        };
    }

    public function getConfidenceLevelAttribute(): string
    {
        return match(true) {
            $this->confidence_score >= 90 => 'Very High',
            $this->confidence_score >= 75 => 'High',
            $this->confidence_score >= 50 => 'Medium',
            $this->confidence_score >= 25 => 'Low',
            default => 'Very Low',
        };
    }
}
