<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use App\Services\ProductCodeService;
use App\Support\ProductName;
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
        'sort_order' => 'integer',
        'active' => 'boolean',
        'show_on_home' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Veritabanı cascade silmesi model olaylarını çalıştırmaz. Görselleri
        // önce model üzerinden silerek ProductImageObserver'ın depodaki gerçek
        // dosyaları da temizlemesini sağla.
        static::deleting(function (Product $product): void {
            $product->images()->get()->each->delete();
        });

        static::creating(function (Product $product): void {
            // Kod sistem tarafından atanır; panelde elle girilmez.
            if (blank($product->code)) {
                $product->code = app(ProductCodeService::class)->next();
            } else {
                app(ProductCodeService::class)->reserve((string) $product->code);
            }

            if ((int) $product->sort_order < 1) {
                $product->sort_order = ((int) Product::query()->max('sort_order')) + 1;
            }
        });

        static::saving(function (Product $product): void {
            $product->price = $product->price_usd;
            $product->currency = 'USD';

            // Mükerrer ad kontrolünün dayandığı anahtar; panelden de, içe
            // aktarmadan da gelse burada üretilir. Ad değişince link yenilenir.
            $product->name_key = ProductName::key($product->name);

            if ($product->name_key !== '' && $product->slug !== $product->name_key) {
                $product->slug = $product->name_key;
            }
        });
    }

    public function priceForLocale(string $locale, ?array $rates = null): mixed
    {
        $usd = (float) $this->price_usd;

        return match ($locale) {
            'ar', 'fa' => $usd,
            'tr' => isset($rates['USD']) && (float) $rates['USD'] > 0
                ? round($usd / (float) $rates['USD'], 2)
                : $usd,
            default => isset($rates['USD'], $rates['EUR']) && (float) $rates['USD'] > 0
                ? round($usd * ((float) $rates['EUR'] / (float) $rates['USD']), 2)
                : $usd,
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
