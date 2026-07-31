<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasUuidKey;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Sipariş kalemi ürün adını/kodunu kendi içinde tutar (ürün sonradan
     * silinse bile sipariş okunabilir kalsın diye). İlişki yalnızca sipariş
     * detayındaki görsel için var; ürün silinmişse null döner.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
