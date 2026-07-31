<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Bir üründe yalnızca bir görsel kapak (is_primary) olabilir.
 */
class PrimaryProductImageTest extends TestCase
{
    public function test_toggling_primary_clears_the_other_toggle_immediately(): void
    {
        $this->actingAs(User::query()->firstOrFail());

        $product = $this->productWithTwoImages();
        $component = Livewire::test(EditProduct::class, ['record' => $product->getKey()]);
        $keys = array_keys($component->get('data')['images']);

        // İkinci görselin kapak toggle'ı açılır: KAYDETMEDEN, aynı anda
        // birincisinin toggle'ı kapanmalı.
        $component->set("data.images.{$keys[1]}.is_primary", true);

        $state = $component->get('data')['images'];

        $this->assertFalse($state[$keys[0]]['is_primary']);
        $this->assertTrue($state[$keys[1]]['is_primary']);
    }

    public function test_only_one_image_stays_primary_in_database(): void
    {
        $product = $this->productWithTwoImages();
        [$first, $second] = $product->images()->orderBy('sort_order')->get()->all();

        // İkisi de is_primary=true kaydedildi; model son kaydedileni bırakır.
        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);

        // Geri alma: ilkini kapak yapınca ikincisi düşer.
        // (Toplu güncelleme modeli bypass ettiği için taze örnekle kaydediyoruz.)
        $first->fresh()->update(['is_primary' => true]);

        $this->assertTrue($first->fresh()->is_primary);
        $this->assertFalse($second->fresh()->is_primary);
    }

    public function test_turning_off_the_only_primary_keeps_it_on(): void
    {
        $this->actingAs(User::query()->firstOrFail());

        $product = $this->productWithTwoImages();
        $component = Livewire::test(EditProduct::class, ['record' => $product->getKey()]);
        $keys = array_keys($component->get('data')['images']);
        $primaryKey = collect($keys)->first(
            fn (string $key): bool => (bool) $component->get('data')['images'][$key]['is_primary']
        );

        // Tek açık kapağı kapatmaya çalışmak: geri açılmalı, ürün kapaksız kalamaz.
        $component->set("data.images.{$primaryKey}.is_primary", false);

        $this->assertTrue($component->get('data')['images'][$primaryKey]['is_primary']);
    }

    public function test_deleting_the_primary_image_promotes_the_next_one(): void
    {
        $product = $this->productWithTwoImages();
        $primary = $product->images()->where('is_primary', true)->firstOrFail();

        $primary->delete();

        $this->assertSame(1, $product->images()->where('is_primary', true)->count());
    }

    private function productWithTwoImages(): Product
    {
        $category = Category::query()->create([
            'name' => 'Test',
            'name_i18n' => ['tr' => 'Test'],
            'slug' => 'test-kategori',
            'active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'code' => 'IMG-1',
            'slug' => 'img-1',
            'name' => ['tr' => 'Ürün'],
            'description' => ['tr' => 'Açıklama'],
            'price' => 1,
            'currency' => 'TRY',
            'price_try' => 1,
            'price_usd' => 1,
            'price_eur' => 1,
            'pack_size' => 1,
            'stock_status' => 'in_stock',
            'active' => true,
        ]);

        $product->images()->create([
            'storage_path' => 'a.webp',
            'alt' => ['tr' => 'a'],
            'sort_order' => 0,
            'is_primary' => true,
        ]);
        $product->images()->create([
            'storage_path' => 'b.webp',
            'alt' => ['tr' => 'b'],
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        return $product;
    }
}
