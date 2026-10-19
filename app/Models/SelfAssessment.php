<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SelfAssessment extends Model
{
    protected $fillable = [
        'indicator_id',
        'submitted_by_id',
        'periode',
        'description',
        'score',
        'supporting_doc',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'score'        => 'integer',
            'submitted_at' => 'datetime',
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    public function isDraft(): bool      { return $this->status === 'DRAFT'; }
    public function isSubmitted(): bool  { return $this->status === 'SUBMITTED'; }
    public function isValidated(): bool  { return $this->status === 'VALIDATED'; }
    public function isRejected(): bool   { return $this->status === 'REJECTED'; }

    // ─────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(AssessmentIndicator::class, 'indicator_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function validations(): HasMany
    {
        return $this->hasMany(AssessmentValidation::class, 'self_assessment_id');
    }

    /** Ambil validasi terbaru */
    public function latestValidation(): HasMany
    {
        return $this->hasMany(AssessmentValidation::class, 'self_assessment_id')
            ->latest('validated_at');
    }
}
