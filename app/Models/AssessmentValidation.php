<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentValidation extends Model
{
    public $timestamps = false; // Hanya punya validated_at

    protected $fillable = [
        'self_assessment_id',
        'validator_id',
        'status',
        'validated_score',
        'notes',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'validated_score' => 'integer',
            'validated_at'    => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    public function isApproved(): bool        { return $this->status === 'APPROVED'; }
    public function isRejected(): bool        { return $this->status === 'REJECTED'; }
    public function needsRevision(): bool     { return $this->status === 'REVISION_NEEDED'; }

    // ─────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────

    public function selfAssessment(): BelongsTo
    {
        return $this->belongsTo(SelfAssessment::class, 'self_assessment_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_id');
    }
}
