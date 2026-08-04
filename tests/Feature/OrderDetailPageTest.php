<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Support\Multilingual;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sipariş detay sayfası: kalemler, müşteri/adres kartları, aşama çizelgesi ve
 * durumu tek butonla ilerletme.
 */
class OrderDetailPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
        Cache::put('exchange:try:usd-eur:fresh', [
            'USD' => 0.04,
            'EUR' => 0.037,
            'source' => 'Test',
            'date' => now()->toDateString(),
        ]);
    }

    /**
     * Katalog fiyatı olan bir ürün: seed edilen ürünlerde fiyat boş.
     */
    private function pricedProduct(): Product
    {
        $product = Product::query()->firstOrFail();
        $product->forceFill(['price_usd' => 10])->save();

        return $product->refresh();
    }

    private function order(array $attributes = []): Order
    {
        $order = Order::query()->create([
            'order_number' => 'MG-TEST-'.uniqid(),
            'customer_name' => 'Ahmet Yılmaz',
            'phone' => '0532 123 45 67',
            'address' => 'Atatürk Caddesi No: 123, Kadıköy, İstanbul',
            'status' => 'new',
            'total' => 1700,
            'currency' => 'TRY',
            ...$attributes,
        ]);

        $order->items()->create([
            'product_name' => 'Test Elbise',
            'product_code' => 'TE-01',
            'size' => 'M',
            'color' => 'Siyah',
            'quantity' => 2,
            'unit_price' => 850,
            'line_total' => 1700,
        ]);

        return $order;
    }

    public function test_detail_page_shows_customer_items_and_stages(): void
    {
        $order = $this->order();

        Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->assertOk()
            ->assertSee('Ahmet Yılmaz')
            ->assertSee('Test Elbise')
            ->assertSee('Genel toplam')
            ->assertSee('Hazırlanıyor')
            ->assertSee('Kargoda');
    }

    public function test_status_is_advanced_one_step_at_a_time(): void
    {
        $order = $this->order(['status' => 'preparing']);

        Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->callAction('advanceStatus');

        $this->assertSame('shipped', $order->fresh()->status);
    }

    public function test_order_can_be_cancelled(): void
    {
        $order = $this->order();

        Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->callAction('cancelOrder');

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_completed_order_has_no_next_step(): void
    {
        $order = $this->order(['status' => 'completed']);

        Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->assertActionHidden('advanceStatus')
            ->assertActionHidden('cancelOrder');
    }

    public function test_items_are_updated_in_place_and_the_order_total_is_recalculated(): void
    {
        $order = $this->order();
        $existing = $order->items->first();
        $product = $this->pricedProduct();

        $page = Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->mountFormComponentAction('orderItems', 'editItems');

        // Repeater satırları rastgele anahtarlarla tutulur; mevcut satırı
        // anahtarıyla bulup üstünde değişiklik yaparız.
        $items = $page->get('mountedActions.0.data.items');
        $key = array_key_first($items);

        $items[$key] = [
            ...$items[$key],
            'product_id' => $product->getKey(),
            'product_name' => Multilingual::tr($product->name),
            'product_code' => $product->code,
            'quantity' => 3,
            // Birim fiyat formda yok: katalogdan okunuyor.
            'unit_price' => null,
        ];

        $page->set('mountedActions.0.data.items', $items)
            ->callMountedAction()
            ->assertHasNoErrors();

        $order->refresh();
        $item = $order->items->sole();

        // Satır silinip yeniden yaratılmaz: kalem kimliği korunur.
        $this->assertSame($existing->getKey(), $item->getKey());
        $this->assertSame($product->getKey(), $item->product_id);
        // Birim fiyat ürünün katalog fiyatı, satır toplamı adet × fiyat.
        $expected = number_format($this->tryPrice($product) * 3, 2, '.', '');
        $this->assertSame($expected, $item->line_total);
        // Sipariş toplamı da kalemlerden türetilir.
        $this->assertSame($expected, $order->total);
    }

    public function test_phone_is_shown_and_linked_with_the_country_code(): void
    {
        // Vitrin numarayı kullanıcının yazdığı gibi saklıyor; ülke kodu yok.
        $order = $this->order(['phone' => '5355159198']);

        Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->assertSee('0535 515 91 98')
            ->assertSee('tel:+905355159198', escape: false)
            ->assertSee('https://wa.me/905355159198', escape: false);
    }

    public function test_customer_details_are_edited_through_the_card_action(): void
    {
        $order = $this->order();

        Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->callFormComponentAction('customer', 'editCustomer', data: [
                'customer_name' => 'Ayşe Demir',
                'phone' => '0555 111 22 33',
                'address' => 'Yeni adres',
            ])
            ->assertHasNoFormComponentActionErrors();

        $order->refresh();

        $this->assertSame('Ayşe Demir', $order->customer_name);
        $this->assertSame('0555 111 22 33', $order->phone);
        $this->assertSame('Yeni adres', $order->address);
    }

    public function test_a_cargo_company_outside_the_list_can_be_added(): void
    {
        // Kargo alanı ancak sipariş hazırlanmaya başlayınca açılıyor.
        $order = $this->order(['status' => 'preparing']);

        Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->callFormComponentAction('cargo_company', 'createOption', data: [
                'name' => 'Kolay Gelsin',
            ])
            ->assertHasNoFormComponentActionErrors()
            ->assertSet('data.cargo_company', 'Kolay Gelsin');
    }

    public function test_an_order_can_be_created_with_items_and_the_total_is_derived(): void
    {
        $product = $this->pricedProduct();

        Livewire::test(CreateOrder::class)
            ->fillForm([
                'order_number' => 'MG-TEST-CREATE',
                'customer_name' => 'Yeni Müşteri',
                'phone' => '05551112233',
                'address' => 'Test Mahallesi No: 1, İstanbul',
                'status' => 'new',
                'currency' => 'TRY',
                'items' => [
                    [
                        'product_id' => $product->getKey(),
                        'product_name' => Multilingual::tr($product->name),
                        'product_code' => $product->code,
                        'quantity' => 2,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            // Kaydeden kullanıcı sipariş listesine döner.
            ->assertRedirect(OrderResource::getUrl('index'));

        $order = Order::query()->where('order_number', 'MG-TEST-CREATE')->sole();
        $item = $order->items->sole();

        $expected = number_format($this->tryPrice($product) * 2, 2, '.', '');

        $this->assertSame($product->getKey(), $item->product_id);
        $this->assertSame($expected, $item->line_total);
        // Toplam formda girilmiyor, kalemlerden türetiliyor.
        $this->assertSame($expected, $order->total);
    }

    private function tryPrice(Product $product): float
    {
        return (float) $product->priceForLocale(
            'tr',
            app(ExchangeRateService::class)->ratesFromTry(),
        );
    }

    public function test_saving_the_detail_page_stores_changes(): void
    {
        $order = $this->order();

        Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->fillForm([
                'status' => 'preparing',
                'cargo_company' => 'Aras Kargo',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            // Yönlendirme sunucudan değil, bildirim görüldükten sonra
            // tarayıcıdan yapılır.
            ->assertNoRedirect();

        $order->refresh();

        $this->assertSame('preparing', $order->status);
        $this->assertSame('Aras Kargo', $order->cargo_company);
    }

    public function test_every_row_in_the_list_links_to_the_order(): void
    {
        $order = $this->order();

        Livewire::test(ListOrders::class)
            ->assertOk()
            ->assertSee(OrderResource::getUrl('edit', ['record' => $order]), escape: false)
            ->assertSee('Düzenle');
    }

    public function test_cargo_company_is_locked_until_the_order_is_being_prepared(): void
    {
        $order = $this->order(['status' => 'new']);

        Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->assertFormFieldDisabled('cargo_company')
            ->fillForm(['status' => 'preparing'])
            ->assertFormFieldEnabled('cargo_company');
    }

    public function test_closed_orders_cannot_be_edited(): void
    {
        $order = $this->order(['status' => 'completed']);

        Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->assertDontSee('Kalemleri düzenle');
    }
}
