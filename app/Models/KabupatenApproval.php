<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * KabupatenApproval
 *
 * Mewakili keputusan kabupaten/kota atas submission assessment kecamatan.
 * Satu baris per (kecamatan_user_id, assessment_id, periode).
 *
 * Status:
 *   PENDING          — Belum ditinjau (otomatis dibuat saat kecamatan submit)
 *   APPROVED         — Disetujui kabupaten → bisa dilihat validator tim teknis
 *   NEEDS_REVISION   — Perlu perbaikan → kecamatan harus revisi
 */
class KabupatenApproval extends Model
{
    protected $table = 'kabupaten_approvals';

    protected $fillable = [
        'kecamatan_user_id',
        'assessment_id',
        'periode',
        'kabupaten_user_id',
        'status',
        'notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    public function isPending(): bool        { return $this->status === 'PENDING'; }
    public function isApproved(): bool       { return $this->status === 'APPROVED'; }
    public function needsRevision(): bool    { return $this->status === 'NEEDS_REVISION'; }

    // ─────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────

    public function kecamatanUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kecamatan_user_id');
    }

    public function kabupatenUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kabupaten_user_id');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }
}
