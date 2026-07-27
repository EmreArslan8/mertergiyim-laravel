<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = ['created_at' => 'datetime'];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
