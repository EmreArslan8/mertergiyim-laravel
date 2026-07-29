<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = [
        // jsonb çok dilli alanlar
        'name' => 'array',
        'description' => 'array',
        'price' => 'decimal:2',
        'price_try' => 'decimal:2',
        'price_usd' => 'decimal:2',
        'price_eur' => 'decimal:2',
        'pack_size' => 'integer',
        'pack_contents' => 'array',
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            $product->price = $product->price_try;
            $product->currency = 'TRY';
        });
    }

    public function priceForLocale(string $locale, ?array $rates = null): mixed
    {
        return match ($locale) {
            'tr' => $this->price_try ?? $this->price,
            'ar', 'fa' => isset($rates['USD'])
                ? round((float) ($this->price_try ?? $this->price) * $rates['USD'], 2)
                : ($this->price_usd ?? $this->price),
            default => isset($rates['EUR'])
                ? round((float) ($this->price_try ?? $this->price) * $rates['EUR'], 2)
                : ($this->price_eur ?? $this->price),
        };
    }

    public function currencyForLocale(string $locale): string
    {
        return match ($locale) {
            'tr' => 'TRY',
            'ar', 'fa' => 'USD',
            default => 'EUR',
        };
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
