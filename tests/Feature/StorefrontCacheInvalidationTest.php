<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\StorefrontRepository;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Ürün sayfası cache anahtarı ile temizleme anahtarı ayrışmamalı.
 *
 * Geçmişte repository "storefront:product:v2:<slug>" yazarken temizleyici
 * "storefront:product:<slug>" siliyordu; panelden yapılan düzenlemeler
 * vitrinde bir saat boyunca görünmüyordu.
 */
class StorefrontCacheInvalidationTest extends TestCase
{
    public function test_saving_a_product_clears_its_page_cache(): void
    {
        $suffix = uniqid();
        $product = Product::create([
            'code' => 'CACHE-'.$suffix,
            'slug' => 'cache-'.$suffix,
            'name' => ['tr' => 'Cache Testi'],
            'description' => ['tr' => 'Test'],
            'price' => 100,
            'price_try' => 100,
            'currency' => 'TRY',
            'stock_status' => 'in_stock',
            'active' => true,
        ]);

        $key = StorefrontRepository::productCacheKey($product->slug);

        try {
            app(StorefrontRepository::class)->product($product->slug);
            $this->assertTrue(Cache::has($key), 'Ürün sayfası verisi cache\'lenmedi.');

            $product->stock_status = 'low_stock';
            $product->save();

            $this->assertFalse(Cache::has($key), 'Kayıt sonrası ürün sayfası cache\'i temizlenmedi.');
        } finally {
            Cache::forget($key);
            $product->delete();
        }
    }

    public function test_repository_and_invalidator_agree_on_the_key(): void
    {
        // Anahtar tek sabitten üretilir; sürüm eki değişse bile ikisi eşleşir.
        $this->assertSame(
            'storefront:'.StorefrontRepository::PRODUCT_KEY.'ornek-slug',
            StorefrontRepository::productCacheKey('ornek-slug'),
        );
    }

    public function test_home_uses_configured_product_limit_and_newest_first_order(): void
    {
        $products = Product::query()->active()->take(3)->get();
        $this->assertCount(3, $products);

        Product::query()->update(['created_at' => now()->subYear()]);
        $products[0]->update(['created_at' => now()->subMinutes(3)]);
        $products[1]->update(['created_at' => now()->subMinute()]);
        $products[2]->update(['created_at' => now()->subMinutes(2)]);

        $setting = SiteSetting::query()->findOrFail('storefront');
        $value = $setting->value;
        data_set($value, 'general.homeProductLimit', 2);
        $setting->update(['value' => $value]);
        Cache::forget('storefront:home');

        $homeProducts = app(StorefrontRepository::class)->home()['products'];

        $this->assertCount(2, $homeProducts);
        $this->assertSame(
            [$products[1]->id, $products[2]->id],
            collect($homeProducts)->pluck('id')->all(),
        );
    }

    public function test_homepage_catalog_copy_comes_from_site_settings(): void
    {
        $setting = SiteSetting::query()->findOrFail('storefront');
        $value = $setting->value;
        data_set($value, 'tr.homeFeaturedTitle', 'Panelden Yönetilen Başlık');
        data_set($value, 'tr.homeOrderNotice', '<p>Panelden <strong>yönetilen</strong> ana sayfa açıklaması.</p>');
        $setting->update(['value' => $value]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Panelden Yönetilen Başlık')
            ->assertSee('<p>Panelden <strong>yönetilen</strong> ana sayfa açıklaması.</p>', false);
    }

    public function test_homepage_uses_its_own_seo_settings(): void
    {
        $setting = SiteSetting::query()->findOrFail('storefront');
        $value = $setting->value;
        data_set($value, 'tr.homeSeoTitle', 'Panelden Ana Sayfa SEO Başlığı');
        data_set($value, 'tr.homeSeoDescription', 'Panelden ana sayfaya özel SEO açıklaması.');
        data_set($value, 'tr.homeSeoKeywords', 'ana sayfa, toptan giyim');
        $setting->update(['value' => $value]);

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Panelden Ana Sayfa SEO Başlığı</title>', false)
            ->assertSee('content="Panelden ana sayfaya özel SEO açıklaması."', false)
            ->assertSee('content="ana sayfa, toptan giyim"', false);
    }
}
