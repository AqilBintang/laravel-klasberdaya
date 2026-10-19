<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentCategory extends Model
{
    protected $fillable = [
        'assessment_id',
        'version_id',
        'code',
        'name',
        'description',
        'order',
        'scoring_rule',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'scoring_rule' => 'array',  // JSON kolom
            'is_active'    => 'boolean',
            'order'        => 'integer',
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(AssessmentVersion::class, 'version_id');
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(AssessmentIndicator::class, 'category_id')->orderBy('number');
    }
}
