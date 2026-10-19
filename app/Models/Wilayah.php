<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wilayah extends Model
{
    public $timestamps = false; // Tabel wilayah tidak punya created_at/updated_at

    protected $table = 'wilayah';

    protected $fillable = [
        'kode',
        'nama',
        'level',
        'parent_id',
        'lat',
        'lng',
        'luas',
        'penduduk',
        'path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'lat'      => 'float',
            'lng'      => 'float',
            'luas'     => 'float',
            'penduduk' => 'float',
            'level'    => 'integer',
            'status'   => 'integer',
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Helpers level
    // ─────────────────────────────────────────────────────────

    public function isProvinsi(): bool   { return $this->level === 1; }
    public function isKabupaten(): bool  { return $this->level === 2; }
    public function isKecamatan(): bool  { return $this->level === 3; }

    // ─────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Wilayah::class, 'parent_id');
    }

    /** Kecamatan-kecamatan di bawah kabupaten ini */
    public function kecamatans(): HasMany
    {
        return $this->hasMany(Wilayah::class, 'parent_id')->where('level', 3);
    }

    public function usersAsKabupaten(): HasMany
    {
        return $this->hasMany(User::class, 'kabupaten_id');
    }

    public function usersAsKecamatan(): HasMany
    {
        return $this->hasMany(User::class, 'kecamatan_id');
    }

    // ─────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────

    public function scopeProvinsi($query)   { return $query->where('level', 1); }
    public function scopeKabupaten($query)  { return $query->where('level', 2); }
    public function scopeKecamatan($query)  { return $query->where('level', 3); }
}
