<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasUuidKey;

    protected $table = 'currencies';

    protected $guarded = [];

    protected $casts = ['is_default' => 'boolean', 'sort_order' => 'integer'];

    protected static function booted(): void
    {
        // Yeni para biriminde sıra no verilmemişse otomatik sona ekle. Manuel
        // sıralama (tabloda sürükle-bırak) sonradan sort_order'ı günceller.
        static::creating(function (self $currency): void {
            if (blank($currency->sort_order)) {
                $currency->sort_order = (int) static::max('sort_order') + 1;
            }
        });
    }
}
