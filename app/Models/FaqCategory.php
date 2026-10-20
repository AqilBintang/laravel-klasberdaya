<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaqCategory extends Model
{
    protected $fillable = ['name', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Relasi ke FAQ items dalam kategori ini.
     */
    public function items(): HasMany
    {
        return $this->hasMany(FaqItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Hanya items yang aktif.
     */
    public function activeItems(): HasMany
    {
        return $this->hasMany(FaqItem::class)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id');
    }

    /**
     * Scope: hanya kategori aktif, urut sort_order.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
