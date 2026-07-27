<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * StorefrontRepository'nin Cache::remember anahtarlarını temizler.
 *
 * Anahtarlar: storefront:currencies, storefront:chrome, storefront:home,
 * storefront:product:<slug>.
 */
class StorefrontCache
{
    public static function flush(): void
    {
        foreach (['currencies', 'chrome', 'home'] as $key) {
            Cache::forget('storefront:'.$key);
        }

        rescue(function () {
            Product::query()->pluck('slug')->each(
                fn ($slug) => Cache::forget('storefront:product:'.$slug)
            );
        }, report: false);
    }
}
