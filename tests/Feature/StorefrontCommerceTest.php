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

    public function test_customer_can_create_an_order_from_cart_and_stock_is_reduced(): void
    {
        [$categoryId, $productId, $variantId] = $this->insertProduct();

        $response = $this->post('/tr/siparisler', [
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
        $this->assertStringContainsString('/tr/siparis-basarili/', $response->headers->get('Location'));

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
        $this->assertSame(3, DB::table('product_variants')->where('id', $variantId)->value('stock_quantity'));

        $this->get('/tr/siparis-basarili/'.$order->tracking_code)
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

        $this->get('/tr/kategori')->assertOk()->assertSee('KATEGORİLER')->assertSee('Takımlar');
        $this->get('/tr/kategori/takimlar')->assertOk()->assertSee('Takımlar');
        $this->get('/tr/sepet')->assertOk()->assertSee('Sepetiniz');
        $this->get('/tr/multimedya')->assertOk()->assertSee('Yeni Koleksiyon');
        $this->get('/tr/iletisim')->assertOk()->assertSee('İletişim');

        $this->post('/tr/iletisim', [
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

    public function test_package_price_content_and_size_stocks_are_applied_to_order(): void
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

        $this->get('/tr/product/test-urun')
            ->assertOk()
            ->assertSee('M: 5', escape: false)
            ->assertSee('Paket toplam: 6.000');

        $this->post('/tr/siparisler', [
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
        $this->assertSame(0, DB::table('product_variants')->where('id', $variantId)->value('stock_quantity'));
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'unit_price' => 6000,
            'quantity' => 2,
        ]);
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
            'price' => 1200,
            'price_try' => 1200,
            'price_usd' => 30,
            'price_eur' => 28,
            'currency' => 'TRY',
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
}
