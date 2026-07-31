<?php

namespace Tests\Feature;

use App\Models\Product;
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
}
