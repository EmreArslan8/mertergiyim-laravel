<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Color;
use App\Models\ContentPage;
use App\Models\Currency;
use App\Models\HeroSlide;
use App\Models\Language;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\SiteLink;
use App\Models\SiteSetting;
use App\Models\Size;
use App\Services\StorefrontRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * StorefrontRepository'nin Cache::remember anahtarlarını temizler.
 *
 * Anahtarlar: storefront:currencies, storefront:chrome, storefront:home,
 * storefront:sitemap, storefront:blog(:<slug>), storefront:page:<slug> ve
 * ürün sayfası için StorefrontRepository::productCacheKey() (sürüm ekli).
 */
class StorefrontCache
{
    public static function flushFor(Model $model): void
    {
        if ($model instanceof Product) {
            self::forgetMany(['home', 'sitemap']);
            Cache::forget(StorefrontRepository::productCacheKey($model->slug));
            $originalSlug = $model->getOriginal('slug');
            if ($originalSlug && $originalSlug !== $model->slug) {
                Cache::forget(StorefrontRepository::productCacheKey($originalSlug));
            }

            return;
        }

        if ($model instanceof ProductImage || $model instanceof ProductVariant) {
            Cache::forget('storefront:home');
            $slug = Product::query()->whereKey($model->product_id)->value('slug');
            if ($slug) {
                Cache::forget(StorefrontRepository::productCacheKey($slug));
            }

            return;
        }

        if ($model instanceof Category) {
            self::forgetMany(['chrome', 'home', 'sitemap']);

            return;
        }

        if ($model instanceof Size || $model instanceof Color) {
            Product::query()->pluck('slug')->each(
                fn ($slug) => Cache::forget(StorefrontRepository::productCacheKey($slug))
            );

            return;
        }

        if ($model instanceof Currency) {
            Cache::forget('storefront:currencies');

            return;
        }

        if ($model instanceof Language || $model instanceof SiteLink) {
            Cache::forget('storefront:chrome');

            return;
        }

        if ($model instanceof SiteSetting) {
            self::forgetMany(['chrome', 'sitemap']);

            return;
        }

        if ($model instanceof HeroSlide) {
            Cache::forget('storefront:home');

            return;
        }

        if ($model instanceof ContentPage) {
            self::forgetMany(['sitemap', 'page:'.$model->slug]);

            return;
        }

        if ($model instanceof BlogPost) {
            self::forgetMany(['blog', 'sitemap', 'blog:'.$model->slug]);

            return;
        }

        if ($model instanceof Media) {
            return;
        }

        self::flush();
    }

    public static function flush(): void
    {
        foreach (['currencies', 'chrome', 'home', 'blog', 'sitemap'] as $key) {
            Cache::forget('storefront:'.$key);
        }

        rescue(function () {
            Product::query()->pluck('slug')->each(
                fn ($slug) => Cache::forget(StorefrontRepository::productCacheKey($slug))
            );
        }, report: false);

        rescue(function () {
            ContentPage::query()->pluck('slug')->each(
                fn ($slug) => Cache::forget('storefront:page:'.$slug)
            );
            BlogPost::query()->pluck('slug')->each(
                fn ($slug) => Cache::forget('storefront:blog:'.$slug)
            );
        }, report: false);
    }

    /**
     * @param  array<int, string>  $keys
     */
    private static function forgetMany(array $keys): void
    {
        foreach ($keys as $key) {
            Cache::forget('storefront:'.$key);
        }
    }
}
