<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RubricItem extends Model
{
    protected $fillable = [
        'rubric_id',
        'indicator_id',
        'score1',
        'score2',
        'score3',
        'score4',
    ];

    /**
     * Kembalikan deskripsi untuk skor tertentu (1–4).
     */
    public function getScoreDescription(int $score): string
    {
        return match ($score) {
            1 => $this->score1,
            2 => $this->score2,
            3 => $this->score3,
            4 => $this->score4,
            default => '-',
        };
    }

    // ─────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(AssessmentRubric::class, 'rubric_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(AssessmentIndicator::class, 'indicator_id');
    }
}
