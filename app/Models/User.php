<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password_hash',   // Sesuai kolom di DB (bukan 'password')
        'role',
        'kabupaten',       // Legacy string
        'kecamatan',       // Legacy string
        'kabupaten_id',
        'kecamatan_id',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
        ];
    }

    /**
     * Override getAuthPassword agar Laravel auth menggunakan password_hash.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    // ─────────────────────────────────────────────────────────
    // Helper: cek role
    // ─────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === 'SUPER_ADMIN';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['SUPER_ADMIN', 'ADMIN']);
    }

    public function isValidator(): bool
    {
        return $this->role === 'VALIDATOR';
    }

    public function isKecamatan(): bool
    {
        return $this->role === 'USER';
    }

    // ─────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────

    public function kabupatenWilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'kabupaten_id');
    }

    public function kecamatanWilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'kecamatan_id');
    }

    public function selfAssessments(): HasMany
    {
        return $this->hasMany(SelfAssessment::class, 'submitted_by_id');
    }

    public function validations(): HasMany
    {
        return $this->hasMany(AssessmentValidation::class, 'validator_id');
    }

    public function assessmentVersionsCreated(): HasMany
    {
        return $this->hasMany(AssessmentVersion::class, 'created_by');
    }

    public function assessmentStatuses(): HasMany
    {
        return $this->hasMany(UserAssessmentStatus::class, 'user_id');
    }

    public function draftBackups(): HasMany
    {
        return $this->hasMany(AssessmentDraftBackup::class, 'user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }
}
