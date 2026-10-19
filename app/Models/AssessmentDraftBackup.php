<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentDraftBackup extends Model
{
    use HasUuids; // Laravel auto-generate UUID untuk primary key

    public $timestamps = false; // Hanya punya created_at

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'assessment_id',
        'version_number',
        'answers_json',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'answers_json'   => 'array',
            'version_number' => 'integer',
            'created_at'     => 'datetime',
        ];
    }

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
