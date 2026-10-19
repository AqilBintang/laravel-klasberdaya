<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $fillable = [
        'title',
        'description',
        'periode',
        'status',
        'current_version',
        'last_major_update_at',
    ];

    protected function casts(): array
    {
        return [
            'last_major_update_at' => 'datetime',
            'current_version'      => 'integer',
            'created_at'           => 'datetime',
            'updated_at'           => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    public function isPublished(): bool  { return $this->status === 'PUBLISHED'; }
    public function isDraft(): bool      { return $this->status === 'DRAFT'; }
    public function isRevision(): bool   { return $this->status === 'REVISION'; }
    public function isArchived(): bool   { return $this->status === 'ARCHIVED'; }

    // ─────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────

    public function versions(): HasMany
    {
        return $this->hasMany(AssessmentVersion::class, 'assessment_id');
    }

    public function currentVersionModel(): HasMany
    {
        return $this->hasMany(AssessmentVersion::class, 'assessment_id')
            ->where('version_number', $this->current_version);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(AssessmentCategory::class, 'assessment_id');
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(AssessmentIndicator::class, 'assessment_id');
    }

    public function rubrics(): HasMany
    {
        return $this->hasMany(AssessmentRubric::class, 'assessment_id');
    }

    public function userStatuses(): HasMany
    {
        return $this->hasMany(UserAssessmentStatus::class, 'assessment_id');
    }

    public function draftBackups(): HasMany
    {
        return $this->hasMany(AssessmentDraftBackup::class, 'assessment_id');
    }
}
