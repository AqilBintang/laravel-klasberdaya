<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentRubric extends Model
{
    protected $fillable = [
        'assessment_id',
        'title',
        'description',
    ];

    // ─────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RubricItem::class, 'rubric_id');
    }
}
