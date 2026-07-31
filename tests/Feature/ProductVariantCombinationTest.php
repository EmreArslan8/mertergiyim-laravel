<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use App\Services\AdminOptionService;
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
            ->twice()
            ->andReturn(
                [
                    'name' => [],
                    'description' => [],
                ],
                ['alt' => []],
            ));

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
                ->assertSee('Paket dağılımı')
                ->assertSee('Paket içindeki adet')
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

    public function test_pack_size_uses_the_configured_template_when_it_fits(): void
    {
        $this->actingAs(User::query()->firstOrFail());
        $suffix = uniqid();
        $sizes = collect(['S', 'M', 'L', 'XL'])->map(fn ($name, $index) => Size::create([
            'name' => $name.'-'.$suffix,
            'name_i18n' => ['tr' => $name.'-'.$suffix],
            'sort_order' => 300 + $index,
            'active' => true,
        ]));
        AdminOptionService::flush();

        try {
            // config storefront.pack.templates.4 = [1, 2, 1, 1] (sahadaki 5'li seri)
            $quantities = collect(
                Livewire::test(CreateProduct::class)
                    ->fillForm([
                        'pack_size' => 5,
                        'variant_size_ids' => $sizes->pluck('id')->all(),
                    ])
                    ->get('data.pack_contents')
            )->pluck('quantity', 'size_id');

            $this->assertSame(
                [1, 2, 1, 1],
                $sizes->map(fn ($size): int => $quantities[$size->id])->all()
            );
        } finally {
            Size::query()->whereIn('id', $sizes->pluck('id'))->delete();
            AdminOptionService::flush();
        }
    }

    public function test_pack_size_falls_back_to_computed_distribution_without_a_template(): void
    {
        $this->actingAs(User::query()->firstOrFail());
        $suffix = uniqid();
        $sizes = collect(['S', 'M', 'L', 'XL'])->map(fn ($name, $index) => Size::create([
            'name' => $name.'-'.$suffix,
            'name_i18n' => ['tr' => $name.'-'.$suffix],
            'sort_order' => 400 + $index,
            'active' => true,
        ]));
        AdminOptionService::flush();

        try {
            // 10 adet / 4 beden: kalıp toplamı (5) tutmuyor -> hesaplanır.
            // Taban 2, kalan 2 ortadan küçüğe: M+1, S+1 = 3·3·2·2
            $quantities = collect(
                Livewire::test(CreateProduct::class)
                    ->fillForm([
                        'pack_size' => 10,
                        'variant_size_ids' => $sizes->pluck('id')->all(),
                    ])
                    ->get('data.pack_contents')
            )->pluck('quantity', 'size_id');

            $this->assertSame(
                [3, 3, 2, 2],
                $sizes->map(fn ($size): int => $quantities[$size->id])->all()
            );
        } finally {
            Size::query()->whereIn('id', $sizes->pluck('id'))->delete();
            AdminOptionService::flush();
        }
    }

    public function test_three_size_pack_uses_the_configured_two_two_one_template(): void
    {
        $this->actingAs(User::query()->firstOrFail());
        $suffix = uniqid();
        $sizes = collect(['S', 'M', 'L'])->map(fn ($name, $index) => Size::create([
            'name' => $name.'-'.$suffix,
            'name_i18n' => ['tr' => $name.'-'.$suffix],
            'sort_order' => 100 + $index,
            'active' => true,
        ]));
        AdminOptionService::flush();

        try {
            // 5 adet / 3 beden -> config kalıbı: S:2 M:2 L:1 (sahadaki 5'li seri)
            $quantities = collect(
                Livewire::test(CreateProduct::class)
                    ->fillForm([
                        'pack_size' => 5,
                        'variant_size_ids' => $sizes->pluck('id')->all(),
                    ])
                    ->get('data.pack_contents')
            )->pluck('quantity', 'size_id');

            $this->assertSame(2, $quantities[$sizes[0]->id]);
            $this->assertSame(2, $quantities[$sizes[1]->id]);
            $this->assertSame(1, $quantities[$sizes[2]->id]);
            $this->assertSame(5, $quantities->sum());
        } finally {
            Size::query()->whereIn('id', $sizes->pluck('id'))->delete();
            AdminOptionService::flush();
        }
    }

    public function test_manually_entered_quantities_survive_until_a_redistribute(): void
    {
        $this->actingAs(User::query()->firstOrFail());
        $suffix = uniqid();
        $sizes = collect(['S', 'M', 'L'])->map(fn ($name, $index) => Size::create([
            'name' => $name.'-'.$suffix,
            'name_i18n' => ['tr' => $name.'-'.$suffix],
            'sort_order' => 200 + $index,
            'active' => true,
        ]));
        AdminOptionService::flush();

        try {
            $component = Livewire::test(CreateProduct::class)
                ->fillForm([
                    'pack_size' => 5,
                    'variant_size_ids' => $sizes->pluck('id')->all(),
                ]);

            // Tablodan elle düzenleme: 2/2/1 -> 4/2/1
            $keys = collect($component->get('data.pack_contents'))->keys()->all();
            $component->set('data.pack_contents.'.$keys[0].'.quantity', 4);

            // Renk seçimi gibi ilgisiz bir değişiklik adetleri ezmez.
            $component->set('data.variant_color_ids', []);

            $this->assertSame(
                [4, 2, 1],
                collect($component->get('data.pack_contents'))->pluck('quantity')->all()
            );
        } finally {
            Size::query()->whereIn('id', $sizes->pluck('id'))->delete();
            AdminOptionService::flush();
        }
    }

    public function test_redistribution_uses_the_current_pack_target_and_overrides_manual_values(): void
    {
        $this->actingAs(User::query()->firstOrFail());
        $suffix = uniqid();
        $sizes = collect(['S', 'M', 'L', 'XL'])->map(fn ($name, $index) => Size::create([
            'name' => $name.'-'.$suffix,
            'name_i18n' => ['tr' => $name.'-'.$suffix],
            'sort_order' => 600 + $index,
            'active' => true,
        ]));
        AdminOptionService::flush();

        try {
            $component = Livewire::test(CreateProduct::class)
                ->fillForm([
                    'pack_size' => 5,
                    'variant_size_ids' => $sizes->pluck('id')->all(),
                ]);

            // Elle dokun: 1/2/1/1 -> 4/2/1/1
            $keys = collect($component->get('data.pack_contents'))->keys()->all();
            $component->set('data.pack_contents.'.$keys[0].'.quantity', 4);

            // Hedef 8 (hazır seri butonunun yaptığı şey) + yeniden dağıtım
            // tetikleyicisi: dağılım baştan kurulur, elle girilen 4 gider.
            $component->set('data.pack_size', 8);
            $component->set('data.variant_size_ids', $sizes->pluck('id')->all());

            $quantities = collect($component->get('data.pack_contents'))->pluck('quantity')->all();

            $this->assertSame(8, array_sum($quantities));
            $this->assertSame([2, 2, 2, 2], $quantities);
        } finally {
            Size::query()->whereIn('id', $sizes->pluck('id'))->delete();
            AdminOptionService::flush();
        }
    }

    public function test_pack_size_is_derived_from_the_distribution_total(): void
    {
        $this->actingAs(User::query()->firstOrFail());
        $suffix = uniqid();
        $sizes = collect(['S', 'M', 'L'])->map(fn ($name, $index) => Size::create([
            'name' => $name.'-'.$suffix,
            'name_i18n' => ['tr' => $name.'-'.$suffix],
            'sort_order' => 500 + $index,
            'active' => true,
        ]));
        $color = Color::create([
            'name' => 'Siyah-'.$suffix,
            'name_i18n' => ['tr' => 'Siyah-'.$suffix],
            'hex' => '#000000',
            'active' => true,
        ]);
        $category = Category::create([
            'name' => 'Test-'.$suffix,
            'name_i18n' => ['tr' => 'Test-'.$suffix],
            'slug' => 'test-'.$suffix,
            'active' => true,
        ]);

        $this->mock(TranslateService::class, fn ($mock) => $mock
            ->shouldReceive('translateFields')
            ->twice()
            ->andReturn(
                ['name' => [], 'description' => []],
                ['alt' => []],
            ));

        try {
            $component = Livewire::test(CreateProduct::class)
                ->fillForm([
                    'code' => 'DERIVED-'.$suffix,
                    'slug' => 'derived-'.$suffix,
                    'name' => ['tr' => 'Türetilmiş Paket'],
                    'description' => ['tr' => 'Test'],
                    'price_try' => 100,
                    'category_id' => $category->id,
                    'stock_status' => 'in_stock',
                    'active' => false,
                    'images' => [[
                        'storage_path' => [UploadedFile::fake()->image('derived.jpg', 800, 1000)],
                        'alt' => ['tr' => 'Görsel'],
                        'is_primary' => true,
                        'sort_order' => 0,
                    ]],
                    'variant_size_ids' => $sizes->pluck('id')->all(),
                    'variant_color_ids' => [$color->id],
                ]);

            // Dağılımı elle 3/2/2 = 7 adet yap; paket adedi bunu takip etmeli.
            $keys = collect($component->get('data.pack_contents'))->keys()->all();
            $component->set('data.pack_contents.'.$keys[0].'.quantity', 3);
            $component->set('data.pack_contents.'.$keys[1].'.quantity', 2);
            $component->set('data.pack_contents.'.$keys[2].'.quantity', 2);

            $component->call('create')->assertHasNoFormErrors();

            $product = Product::query()->where('code', 'DERIVED-'.$suffix)->firstOrFail();

            $this->assertSame(7, (int) $product->pack_size);
            $this->assertSame(7, collect($product->pack_contents)->sum('quantity'));
        } finally {
            Product::query()->where('code', 'DERIVED-'.$suffix)->get()->each->delete();
            Size::query()->whereIn('id', $sizes->pluck('id'))->delete();
            $color->delete();
            $category->delete();
            AdminOptionService::flush();
        }
    }
}
