<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;

class LogAnalysis extends Model
{
    protected $fillable = [
        'user_id',
        'original_filename',
        'file_path',
        'file_size',
        'file_hash',
        'log_format',
        'total_entries',
        'threats_detected',
        'processing_status',
        'progress_percentage',
        'entries_processed',
        'analysis_results',
        'processed_at',
        'processing_duration_seconds',
    ];

    protected $appends = [
        'formatted_file_size',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function threatDetections(): HasMany
    {
        return $this->hasMany(ThreatDetection::class);
    }

    protected function casts(): array
    {
        return [
            'analysis_results' => 'array',
            'processed_at' => 'datetime',
            'file_size' => 'integer',
            'total_entries' => 'integer',
            'threats_detected' => 'integer',
            'progress_percentage' => 'integer',
            'entries_processed' => 'integer',
        ];
    }

    public function getFormattedFileSizeAttribute(): string
    {
        return Number::fileSize($this->file_size);
    }

}
