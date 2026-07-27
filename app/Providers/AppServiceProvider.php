<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Color;
use App\Models\Currency;
use App\Models\HeroSlide;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\SiteLink;
use App\Models\SiteSetting;
use App\Models\Size;
use App\Observers\StorefrontCacheObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Vitrin verisini besleyen modeller; her değişiklikte storefront cache'i
     * temizlenir.
     *
     * @var array<int, class-string>
     */
    private const CACHED_MODELS = [
        Product::class,
        ProductImage::class,
        ProductVariant::class,
        Category::class,
        Size::class,
        Color::class,
        Currency::class,
        Language::class,
        HeroSlide::class,
        SiteLink::class,
        SiteSetting::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach (self::CACHED_MODELS as $model) {
            $model::observe(StorefrontCacheObserver::class);
        }
    }
}
