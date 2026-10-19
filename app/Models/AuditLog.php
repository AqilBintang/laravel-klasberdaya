<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false; // Hanya punya created_at

    protected $fillable = [
        'action',
        'user_id',
        'target_id',
        'target_type',
        'details',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'details'    => 'array',
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Static helper — catat log dari mana saja
    // ─────────────────────────────────────────────────────────

    public static function record(
        string $action,
        ?int $userId = null,
        ?string $targetId = null,
        ?string $targetType = null,
        ?array $details = null,
        ?array $metadata = null
    ): self {
        return static::create([
            'action'      => $action,
            'user_id'     => $userId,
            'target_id'   => $targetId,
            'target_type' => $targetType,
            'details'     => $details,
            'metadata'    => $metadata,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
