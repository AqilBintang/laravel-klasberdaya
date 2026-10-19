<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentIndicator extends Model
{
    protected $fillable = [
        'assessment_id',
        'category_id',
        'version_id',
        'number',
        'indicator',
        'max_score',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'number'     => 'integer',
            'max_score'  => 'integer',
            'is_active'  => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssessmentCategory::class, 'category_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(AssessmentVersion::class, 'version_id');
    }

    public function selfAssessments(): HasMany
    {
        return $this->hasMany(SelfAssessment::class, 'indicator_id');
    }

    public function rubricItems(): HasMany
    {
        return $this->hasMany(RubricItem::class, 'indicator_id');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(IndicatorChange::class, 'indicator_id');
    }
}
