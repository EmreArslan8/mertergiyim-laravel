<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontCommerceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->cacheExchangeRates();

        DB::table('languages')->insert([
            'id' => (string) Str::uuid(),
            'code' => 'tr',
            'name' => 'Türkçe',
            'active' => true,
            'sort_order' => 1,
            'created_at' => now(),
        ]);
        DB::table('currencies')->updateOrInsert(['code' => 'TRY'], [
            'id' => (string) Str::uuid(),
            'symbol' => 'TL',
            'position' => 'suffix',
            'is_default' => true,
            'sort_order' => 1,
            'created_at' => now(),
        ]);
    }

    public function test_customer_can_create_an_order_from_cart(): void
    {
        [$categoryId, $productId, $variantId] = $this->insertProduct();

        $response = $this->post('/siparisler', [
            'customer_name' => 'Emre Arslan',
            'phone' => '05320000000',
            'address' => 'Merter, İstanbul',
            'note' => 'Öğleden sonra arayın.',
            'cart' => json_encode([[
                'product_id' => $productId,
                'color' => 'Siyah',
                'quantity' => 2,
            ]]),
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('/siparis-basarili/', $response->headers->get('Location'));

        $order = DB::table('orders')->where('phone', '05320000000')->first();
        $this->assertNotNull($order);
        $this->assertStringStartsWith('MG-', $order->order_number);
        $this->assertNotEmpty($order->tracking_code);
        $this->assertSame('TRY', $order->currency);
        $this->assertEquals(2400.00, (float) $order->total);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => 2,
            'size' => null,
            'color' => 'Siyah',
        ]);
        // Parça stoğu tutulmuyor: sipariş varyant stoğunu değiştirmez.
        $this->assertSame(5, DB::table('product_variants')->where('id', $variantId)->value('stock_quantity'));

        $this->get('/siparis-basarili/'.$order->tracking_code)
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee($order->tracking_code);
    }

    public function test_category_media_cart_and_contact_pages_are_available(): void
    {
        [$categoryId] = $this->insertProduct();

        $mediaPostId = (string) Str::uuid();

        DB::table('media_posts')->insert([
            'id' => $mediaPostId,
            'legacy_media_id' => null,
            'title' => json_encode(['tr' => 'Yeni Koleksiyon']),
            'description' => json_encode(['tr' => 'Sezon çekimi']),
            'active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('media_files')->insert([
            'id' => (string) Str::uuid(),
            'media_post_id' => $mediaPostId,
            'legacy_media_id' => null,
            'type' => 'image',
            'file_path' => 'lookbook/test.jpg',
            'alt' => json_encode(['tr' => 'Yeni Koleksiyon']),
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/kategori')->assertOk()->assertSee('KATEGORİLER')->assertSee('Takımlar');
        $this->get('/kategori/takimlar')->assertOk()->assertSee('Takımlar');
        $this->get('/sepet')->assertOk()->assertSee('Sepetiniz');
        $this->get('/multimedya')->assertOk()->assertSee('Yeni Koleksiyon');
        $this->get('/iletisim')->assertOk()->assertSee('İletişim');

        $this->post('/iletisim', [
            'name' => 'Test Müşteri',
            'phone' => '05000000000',
            'email' => 'test@example.com',
            'subject' => 'Toptan sipariş',
            'message' => 'Bilgi almak istiyorum.',
        ])->assertRedirect();

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Test Müşteri',
            'phone' => '05000000000',
            'locale' => 'tr',
        ]);
    }

    public function test_package_price_and_content_are_applied_to_order(): void
    {
        [, $productId, $variantId] = $this->insertProduct();
        $sizeId = DB::table('product_variants')->where('id', $variantId)->value('size_id');

        DB::table('products')->where('id', $productId)->update([
            'pack_size' => 5,
            'pack_contents' => json_encode([
                ['size_id' => $sizeId, 'quantity' => 5],
            ]),
        ]);
        DB::table('product_variants')->where('id', $variantId)->update(['stock_quantity' => 10]);

        $this->get('/product/test-urun')
            ->assertOk()
            ->assertSee('M: 5', escape: false)
            ->assertSee('Paket toplam: 6.000');

        DB::table('languages')->insert([
            'id' => (string) Str::uuid(),
            'code' => 'en',
            'name' => 'English',
            'active' => true,
            'sort_order' => 2,
            'created_at' => now(),
        ]);
        Cache::flush();
        $this->cacheExchangeRates();

        $this->get('/en/product/test-urun')->assertOk();
        $this->get('/tr/product/test-urun')->assertRedirect('/product/test-urun');

        $this->post('/siparisler', [
            'customer_name' => 'Paket Müşterisi',
            'phone' => '05321111111',
            'address' => 'Merter, İstanbul',
            'cart' => json_encode([[
                'product_id' => $productId,
                'color' => 'Siyah',
                'quantity' => 2,
            ]]),
        ])->assertRedirect();

        $order = DB::table('orders')->where('phone', '05321111111')->first();
        $this->assertNotNull($order);
        $this->assertEquals(12000.00, (float) $order->total);
        // Stok takibi yok: paket siparişi de stoğa dokunmaz.
        $this->assertSame(10, DB::table('product_variants')->where('id', $variantId)->value('stock_quantity'));
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'unit_price' => 6000,
            'quantity' => 2,
        ]);
    }

    public function test_product_video_is_the_second_gallery_item(): void
    {
        [, $productId] = $this->insertProduct();

        DB::table('products')->where('id', $productId)->update([
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
        DB::table('product_images')->insert([
            [
                'id' => (string) Str::uuid(),
                'product_id' => $productId,
                'storage_path' => 'test/one.webp',
                'alt' => json_encode(['tr' => 'Birinci fotoğraf']),
                'sort_order' => 0,
                'is_primary' => true,
            ],
            [
                'id' => (string) Str::uuid(),
                'product_id' => $productId,
                'storage_path' => 'test/two.webp',
                'alt' => json_encode(['tr' => 'İkinci fotoğraf']),
                'sort_order' => 1,
                'is_primary' => false,
            ],
        ]);

        $response = $this->get('/product/test-urun');

        $response->assertOk()
            ->assertSee('data-gallery-video', escape: false)
            ->assertSee('data-gallery-video-iframe', escape: false)
            ->assertSeeInOrder([
                'data-thumb=',
                'data-video-thumb',
                'data-thumb=',
            ], escape: false)
            ->assertDontSee('class="detail-video"', escape: false);
    }

    /**
     * @return array{string, string, string}
     */
    private function insertProduct(): array
    {
        $categoryId = (string) Str::uuid();
        $sizeId = (string) Str::uuid();
        $colorId = (string) Str::uuid();
        $productId = (string) Str::uuid();
        $variantId = (string) Str::uuid();

        DB::table('categories')->insert([
            'id' => $categoryId,
            'name' => 'Takımlar',
            'name_i18n' => json_encode(['tr' => 'Takımlar']),
            'slug' => 'takimlar',
            'active' => true,
            'created_at' => now(),
        ]);
        DB::table('sizes')->insert([
            'id' => $sizeId,
            'name' => 'M',
            'name_i18n' => json_encode(['tr' => 'M']),
            'active' => true,
            'sort_order' => 1,
        ]);
        DB::table('colors')->insert([
            'id' => $colorId,
            'name' => 'Siyah',
            'name_i18n' => json_encode(['tr' => 'Siyah']),
            'hex' => '#000000',
            'active' => true,
            'sort_order' => 1,
        ]);
        DB::table('products')->insert([
            'id' => $productId,
            'category_id' => $categoryId,
            'code' => 'MG-TEST',
            'slug' => 'test-urun',
            'name' => json_encode(['tr' => 'Test Ürün']),
            'description' => json_encode(['tr' => 'Test açıklama']),
            'price' => 30,
            'price_usd' => 30,
            'price_eur' => 28,
            'currency' => 'USD',
            'stock_status' => 'in_stock',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('product_variants')->insert([
            'id' => $variantId,
            'product_id' => $productId,
            'size_id' => $sizeId,
            'color_id' => $colorId,
            'stock_quantity' => 5,
        ]);

        return [$categoryId, $productId, $variantId];
    }

    private function cacheExchangeRates(): void
    {
        Cache::put('exchange:try:usd-eur:fresh', [
            'USD' => 0.025,
            'EUR' => 0.023,
            'source' => 'Test',
            'date' => now()->toDateString(),
        ]);
    }
}
