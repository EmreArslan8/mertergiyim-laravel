<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use App\Services\TranslateService;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class ProductVariantCombinationTest extends TestCase
{
    public function test_negative_product_price_is_rejected(): void
    {
        $this->actingAs(User::query()->firstOrFail());
        $suffix = uniqid();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'code' => 'NEGATIVE-'.$suffix,
                'slug' => 'negative-'.$suffix,
                'name' => ['tr' => 'Negatif Fiyat Testi'],
                'price_try' => -0.01,
                'stock_status' => 'in_stock',
                'active' => false,
            ])
            ->call('create')
            ->assertHasFormErrors(['price_try' => 'min']);

        $this->assertDatabaseMissing('products', ['code' => 'NEGATIVE-'.$suffix]);
    }

    public function test_selected_sizes_and_colors_create_cartesian_variants(): void
    {
        $this->actingAs(User::query()->firstOrFail());
        $suffix = uniqid();
        $sizes = collect(['S', 'M'])->map(fn ($name) => Size::create([
            'name' => $name.'-'.$suffix,
            'name_i18n' => ['tr' => $name.'-'.$suffix],
            'active' => true,
        ]));
        $colors = collect(['Kırmızı', 'Siyah'])->map(fn ($name, $index) => Color::create([
            'name' => $name.'-'.$suffix,
            'name_i18n' => ['tr' => $name.'-'.$suffix],
            'hex' => $index ? '#000000' : '#ff0000',
            'active' => true,
        ]));
        $category = Category::create([
            'name' => 'Test-'.$suffix,
            'name_i18n' => ['tr' => 'Test-'.$suffix],
            'slug' => 'test-'.$suffix,
            'active' => true,
        ]);

        $this->mock(TranslateService::class, fn ($mock) => $mock
            ->shouldReceive('translateFields')
            ->once()
            ->andReturn([
                'name' => [],
                'description' => [],
            ]));

        try {
            Livewire::test(CreateProduct::class)
                ->fillForm([
                    'code' => 'COMBO-'.$suffix,
                    'slug' => 'combo-'.$suffix,
                    'name' => ['tr' => 'Kombinasyon Testi'],
                    'description' => ['tr' => 'Test'],
                    'price_try' => 100,
                    'pack_size' => 5,
                    'category_id' => $category->id,
                    'stock_status' => 'in_stock',
                    'active' => false,
                    'images' => [[
                        'storage_path' => [UploadedFile::fake()->image('combo.jpg', 800, 1000)],
                        'alt' => ['tr' => 'Kombinasyon ürünü'],
                        'is_primary' => true,
                        'sort_order' => 0,
                    ]],
                    'variant_size_ids' => $sizes->pluck('id')->all(),
                    'variant_color_ids' => $colors->pluck('id')->all(),
                    'pack_contents' => [
                        ['size_id' => $sizes[0]->id, 'quantity' => 2],
                        ['size_id' => $sizes[1]->id, 'quantity' => 3],
                    ],
                ])
                ->assertSee('Renk × beden stokları')
                ->assertSee('Satılabilir')
                ->call('create')
                ->assertHasNoFormErrors();

            $product = Product::query()->where('code', 'COMBO-'.$suffix)->firstOrFail();
            $this->assertCount(4, $product->variants);
            $this->assertSame(5, collect($product->pack_contents)->sum('quantity'));
            $this->assertTrue($product->active);
        } finally {
            Product::query()->where('code', 'COMBO-'.$suffix)->get()->each->delete();
            Size::query()->whereIn('id', $sizes->pluck('id'))->delete();
            Color::query()->whereIn('id', $colors->pluck('id'))->delete();
            $category->delete();
        }
    }

    public function test_package_content_total_must_match_package_size(): void
    {
        $this->actingAs(User::query()->firstOrFail());
        $suffix = uniqid();
        $sizes = collect(['S', 'M'])->map(fn ($name) => Size::create([
            'name' => $name.'-'.$suffix,
            'name_i18n' => ['tr' => $name.'-'.$suffix],
            'active' => true,
        ]));

        try {
            Livewire::test(CreateProduct::class)
                ->fillForm([
                    'code' => 'PACK-'.$suffix,
                    'slug' => 'pack-'.$suffix,
                    'name' => ['tr' => 'Paket Testi'],
                    'price_try' => 100,
                    'pack_size' => 5,
                    'stock_status' => 'in_stock',
                    'active' => false,
                    'variant_size_ids' => $sizes->pluck('id')->all(),
                    'pack_contents' => [
                        ['size_id' => $sizes[0]->id, 'quantity' => 1],
                        ['size_id' => $sizes[1]->id, 'quantity' => 2],
                    ],
                ])
                ->call('create')
                ->assertHasFormErrors(['pack_contents']);

            $this->assertDatabaseMissing('products', ['code' => 'PACK-'.$suffix]);
        } finally {
            Size::query()->whereIn('id', $sizes->pluck('id'))->delete();
        }
    }
}
