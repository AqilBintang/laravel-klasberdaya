<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentVersion extends Model
{
    public $timestamps = false; // Hanya punya created_at, bukan updated_at

    protected $fillable = [
        'assessment_id',
        'version_number',
        'title',
        'description',
        'changes_summary',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'created_at'     => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(AssessmentCategory::class, 'version_id');
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(AssessmentIndicator::class, 'version_id');
    }

    public function indicatorChanges(): HasMany
    {
        return $this->hasMany(IndicatorChange::class, 'version_id');
    }
}
