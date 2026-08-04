<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = ['active' => 'boolean', 'sort_order' => 'integer', 'name_i18n' => 'array'];

    protected static function booted(): void
    {
        static::saving(function (Size $size): void {
            $size->name = $size->name_i18n['tr'] ?? $size->name;
        });

        // Yeni bedende sıra no verilmemişse otomatik sona ekle. Manuel sıralama
        // (tabloda sürükle-bırak) sonradan sort_order'ı günceller.
        static::creating(function (Size $size): void {
            if (blank($size->sort_order)) {
                $size->sort_order = (int) static::max('sort_order') + 1;
            }
        });
    }
}
