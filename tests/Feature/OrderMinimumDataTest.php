<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Support\Multilingual;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Siparişin açılabilmesi için gereken asgari bilgi: ad soyad, geçerli telefon,
 * teslimat adresi ve en az bir kalem.
 *
 * Kural iki kapıda da aynı olmalı — müşterinin geçtiği checkout ve personelin
 * kullandığı panel formu. Panelde adres zorunlu değildi ve kalemsiz sipariş
 * kaydedilebiliyordu; bu testler o boşluğun geri gelmesini engeller.
 */
class OrderMinimumDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
    }

    private function orderCount(): int
    {
        return Order::query()->count();
    }

    private function pricedProduct(): Product
    {
        $product = Product::query()->firstOrFail();
        $product->forceFill(['price_try' => 250])->save();

        return $product->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPanelData(Product $product, array $overrides = []): array
    {
        return [
            'order_number' => 'MG-TEST-'.strtoupper(uniqid()),
            'customer_name' => 'Yeni Müşteri',
            'phone' => '05551112233',
            'address' => 'Atatürk Caddesi No: 1, Merter, İstanbul',
            'status' => 'new',
            'currency' => 'TRY',
            'items' => [
                [
                    'product_id' => $product->getKey(),
                    'product_name' => Multilingual::tr($product->name),
                    'product_code' => $product->code,
                    'quantity' => 1,
                ],
            ],
            ...$overrides,
        ];
    }

    // ---- Panel: oluşturma ----

    public function test_panel_order_is_created_when_the_minimum_data_is_complete(): void
    {
        $product = $this->pricedProduct();
        $before = $this->orderCount();

        Livewire::test(CreateOrder::class)
            ->fillForm($this->validPanelData($product))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame($before + 1, $this->orderCount());
    }

    public function test_panel_order_without_an_address_is_rejected(): void
    {
        $product = $this->pricedProduct();
        $before = $this->orderCount();

        Livewire::test(CreateOrder::class)
            ->fillForm($this->validPanelData($product, ['address' => '']))
            ->call('create')
            ->assertHasFormErrors(['address' => 'required']);

        $this->assertSame($before, $this->orderCount());
    }

    public function test_panel_order_without_a_customer_name_is_rejected(): void
    {
        $product = $this->pricedProduct();
        $before = $this->orderCount();

        Livewire::test(CreateOrder::class)
            ->fillForm($this->validPanelData($product, ['customer_name' => '']))
            ->call('create')
            ->assertHasFormErrors(['customer_name' => 'required']);

        $this->assertSame($before, $this->orderCount());
    }

    public function test_panel_order_with_an_unusable_phone_is_rejected(): void
    {
        $product = $this->pricedProduct();
        $before = $this->orderCount();

        Livewire::test(CreateOrder::class)
            ->fillForm($this->validPanelData($product, ['phone' => 'telefon yok']))
            ->call('create')
            ->assertHasFormErrors(['phone']);

        $this->assertSame($before, $this->orderCount());
    }

    public function test_panel_order_without_items_is_rejected(): void
    {
        $product = $this->pricedProduct();
        $before = $this->orderCount();

        Livewire::test(CreateOrder::class)
            ->fillForm($this->validPanelData($product, ['items' => []]))
            ->call('create')
            ->assertHasFormErrors(['items']);

        $this->assertSame($before, $this->orderCount());
    }

    // ---- Panel: güncelleme ----

    public function test_existing_order_cannot_be_saved_with_the_address_cleared(): void
    {
        $order = Order::query()->create([
            'order_number' => 'MG-TEST-EDIT',
            'customer_name' => 'Ahmet Yılmaz',
            'phone' => '0532 123 45 67',
            'address' => 'Atatürk Caddesi No: 123, Kadıköy, İstanbul',
            'status' => 'new',
            'total' => 850,
            'currency' => 'TRY',
        ]);
        $order->items()->create([
            'product_name' => 'Test Elbise',
            'product_code' => 'TE-01',
            'quantity' => 1,
            'unit_price' => 850,
            'line_total' => 850,
        ]);

        // Müşteri bilgileri sayfada salt okunur; düzenleme kart başlığındaki
        // aksiyonun açtığı modalda yapılıyor.
        Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->callFormComponentAction('customer', 'editCustomer', data: [
                'customer_name' => 'Ahmet Yılmaz',
                'phone' => '0532 123 45 67',
                'address' => '',
            ])
            ->assertHasFormComponentActionErrors(['address' => 'required']);

        $this->assertSame(
            'Atatürk Caddesi No: 123, Kadıköy, İstanbul',
            $order->refresh()->address,
        );
    }

    // ---- Vitrin: checkout ----

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(Product $product, array $overrides = []): array
    {
        return [
            'customer_name' => 'Vitrin Müşterisi',
            'phone' => '05551112233',
            'address' => 'Atatürk Caddesi No: 1, Merter, İstanbul',
            'cart' => json_encode([
                ['product_id' => $product->getKey(), 'quantity' => 1],
            ]),
            ...$overrides,
        ];
    }

    public function test_checkout_requires_a_name_phone_and_address(): void
    {
        $product = $this->pricedProduct();
        $before = $this->orderCount();

        foreach (['customer_name', 'phone', 'address'] as $field) {
            $this->post('/tr/siparisler', $this->checkoutPayload($product, [$field => '']))
                ->assertSessionHasErrors($field);
        }

        $this->assertSame($before, $this->orderCount());
    }

    public function test_checkout_rejects_an_unusable_phone(): void
    {
        $product = $this->pricedProduct();
        $before = $this->orderCount();

        $this->post('/tr/siparisler', $this->checkoutPayload($product, ['phone' => 'telefon yok']))
            ->assertSessionHasErrors('phone');

        $this->assertSame($before, $this->orderCount());
    }

    public function test_checkout_rejects_an_empty_cart(): void
    {
        $product = $this->pricedProduct();
        $before = $this->orderCount();

        $this->post('/tr/siparisler', $this->checkoutPayload($product, ['cart' => '[]']))
            ->assertSessionHasErrors('items');

        $this->assertSame($before, $this->orderCount());
    }
}
