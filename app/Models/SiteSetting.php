<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    public $timestamps = false;  // Hanya punya updated_at (managed by DB)
    public $incrementing = false;
    protected $primaryKey = 'key';
    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return [
            'value'      => 'array', // JSON kolom
            'updated_at' => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Static helpers
    // ─────────────────────────────────────────────────────────

    /**
     * Ambil value dari setting berdasarkan key.
     * Returns null jika key tidak ada.
     */
    public static function get(string $key): mixed
    {
        return static::find($key)?->value;
    }

    /**
     * Set atau update value dari setting.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
