<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\User;
use App\Support\UploadTarget;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Stok durumu iki duruma indi: işaretli = stokta, işaretsiz = tükendi.
 *
 * Kolonda hâlâ metin tutuluyor ("in_stock" / "out_of_stock"); kutucuk yalnızca
 * arayüz tarafında. Eski kayıtlardaki "low_stock" değeri stokta sayılır.
 */
class ProductStockCheckboxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
    }

    private function formState(string $stockStatus): array
    {
        $product = Product::query()->firstOrFail();
        $product->forceFill(['stock_status' => $stockStatus])->save();

        return Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->assertOk()
            ->get('data');
    }

    public function test_stokta_olan_urun_isaretli_gelir(): void
    {
        $this->assertTrue($this->formState('in_stock')['stock_status']);
    }

    public function test_tukenen_urun_isaretsiz_gelir(): void
    {
        $this->assertFalse($this->formState('out_of_stock')['stock_status']);
    }

    public function test_eski_low_stock_kaydi_stokta_sayilir(): void
    {
        $this->assertTrue($this->formState('low_stock')['stock_status']);
    }

    public function test_kutucuk_kaldirilinca_urun_tukendi_olur(): void
    {
        $product = Product::query()->firstOrFail();
        $product->forceFill(['stock_status' => 'in_stock', 'price_usd' => 10])->save();

        $this->hazirlaGorseller($product);

        Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->set('data.stock_status', false)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('out_of_stock', $product->refresh()->stock_status);
    }

    public function test_kutucuk_isaretliyken_urun_stokta_kaydedilir(): void
    {
        $product = Product::query()->firstOrFail();
        $product->forceFill(['stock_status' => 'out_of_stock', 'price_usd' => 10])->save();

        $this->hazirlaGorseller($product);

        Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->set('data.stock_status', true)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('in_stock', $product->refresh()->stock_status);
    }

    /**
     * Ürün formu görsel alanını zorunlu tutuyor ve dosyanın diskte gerçekten
     * bulunmasını bekliyor. Kutucuk testi buna takılmasın diye dosyalar
     * yerine konuyor.
     */
    private function hazirlaGorseller(Product $product): void
    {
        if ($product->images()->count() === 0) {
            $product->images()->create([
                'storage_path' => 'deneme.webp',
                'alt' => ['tr' => ''],
                'is_primary' => true,
                'sort_order' => 0,
            ]);
        }

        $disk = Storage::disk(UploadTarget::disk('products'));

        foreach ($product->images()->get() as $image) {
            if (! $disk->exists($image->storage_path)) {
                $disk->put($image->storage_path, 'deneme');
            }
        }
    }
}
