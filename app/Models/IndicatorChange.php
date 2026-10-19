<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorChange extends Model
{
    public $timestamps = false; // Hanya punya created_at

    protected $fillable = [
        'version_id',
        'indicator_id',
        'change_type',
        'old_value',
        'new_value',
        'requires_resubmit',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_value'        => 'array',
            'new_value'        => 'array',
            'requires_resubmit' => 'boolean',
            'created_at'       => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────

    public function version(): BelongsTo
    {
        return $this->belongsTo(AssessmentVersion::class, 'version_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(AssessmentIndicator::class, 'indicator_id');
    }
}
