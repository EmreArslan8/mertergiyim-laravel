<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Tests\TestCase;

/**
 * Toptan satışta parça stoğu tutulmuyor.
 *
 * Panelde beden/renk bazında stok girişi yok (Varyantlar sekmesindeki matris
 * yalnızca paket dağılımını soruyor), dolayısıyla varyantların stock_quantity
 * değeri 0 kalıyor. Stok kontrolü açık kalırsa her sipariş "yeterli stok
 * bulunmuyor" ile reddedilir. Varsayılan davranış: kontrol yapılmaz.
 */
class OrderWithoutStockTrackingTest extends TestCase
{
    private function orderableProduct(): Product
    {
        $product = Product::query()
            ->where('active', true)
            ->whereHas('variants')
            ->firstOrFail();

        $product->forceFill(['price_try' => 250, 'stock_status' => 'in_stock'])->save();

        // Stok takibi yapılmadığı için depoda karşılığı yok.
        $product->variants()->update(['stock_quantity' => 0]);

        return $product->refresh();
    }

    private function payload(Product $product, int $quantity = 4): array
    {
        $variant = $product->variants()->whereNotNull('color_id')->first()
            ?? $product->variants()->first();

        return [
            'customer_name' => 'Toptan Müşteri',
            'phone' => '05551112233',
            'address' => 'Merter, İstanbul',
            'cart' => json_encode([[
                'product_id' => $product->getKey(),
                'color_id' => $variant?->color_id,
                'color' => $variant?->color?->name,
                'quantity' => $quantity,
            ]]),
        ];
    }

    public function test_order_is_accepted_when_stock_is_not_tracked(): void
    {
        $product = $this->orderableProduct();
        $before = Order::query()->count();

        $this->post('/siparisler', $this->payload($product))
            ->assertSessionHasNoErrors();

        $this->assertSame($before + 1, Order::query()->count());
    }
}
