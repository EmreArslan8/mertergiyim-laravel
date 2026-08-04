<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = ['active' => 'boolean', 'sort_order' => 'integer'];

    protected static function booted(): void
    {
        // Yeni dilde sıra no verilmemişse otomatik sona ekle. Manuel sıralama
        // (tabloda sürükle-bırak) sonradan sort_order'ı günceller.
        static::creating(function (self $language): void {
            if (blank($language->sort_order)) {
                $language->sort_order = (int) static::max('sort_order') + 1;
            }
        });
    }
}
