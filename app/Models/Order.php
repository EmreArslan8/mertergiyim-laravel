<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUuidKey;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'telegram_notified_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
