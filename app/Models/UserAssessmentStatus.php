<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAssessmentStatus extends Model
{
    protected $table = 'user_assessment_status';

    protected $fillable = [
        'user_id',
        'assessment_id',
        'current_version',
        'latest_version',
        'status',
        'last_viewed_at',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'current_version'  => 'integer',
            'latest_version'   => 'integer',
            'last_viewed_at'   => 'datetime',
            'last_activity_at' => 'datetime',
            'created_at'       => 'datetime',
            'updated_at'       => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    public function hasUpdate(): bool    { return $this->status === 'HAS_UPDATE'; }
    public function needsRevision(): bool { return $this->status === 'NEEDS_REVISION'; }
    public function isSubmitted(): bool  { return in_array($this->status, ['SUBMITTED', 'RESUBMITTED']); }
    public function isInProgress(): bool { return $this->status === 'IN_PROGRESS'; }

    // ─────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }
}
