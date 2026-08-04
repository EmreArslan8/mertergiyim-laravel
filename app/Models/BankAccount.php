<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        // Yeni kayıtta sıra no verilmemişse otomatik olarak listenin sonuna ekle.
        // Manuel sıralama (tabloda sürükle-bırak) sonradan sort_order'ı günceller.
        static::creating(function (self $account): void {
            if (blank($account->sort_order)) {
                $account->sort_order = (int) static::max('sort_order') + 1;
            }
        });
    }
}
