<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use App\Services\TranslateService;
use App\Support\UploadTarget;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Panelde sadece Türkçe alan var; kayıt anında kalan 9 dil otomatik doldurulur.
 *
 * Gemini çağrısı mock'lanır, gerçek istek atılmaz. Testin oluşturduğu geçici
 * (yayında olmayan) ürün kaydı her testin sonunda silinir.
 */
class AutoTranslateOnSaveTest extends TestCase
{
    private ?Product $product = null;

    private ?Category $category = null;

    private ?Size $size = null;

    private ?Color $color = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());

        $suffix = uniqid();
        $languages = config('storefront.translation.languages');
        $nameTranslations = array_fill_keys($languages, 'Test Product');
        $descriptionTranslations = array_fill_keys($languages, 'Test description');
        $nameTranslations['de'] = 'Test Produkt';
        $descriptionTranslations['de'] = 'Test Beschreibung';
        Storage::disk(UploadTarget::disk('products'))->put('tests/phpunit-product.jpg', 'test-image');

        $this->category = Category::query()->create([
            'name' => 'PHPUnit kategori '.$suffix,
            'name_i18n' => ['tr' => 'PHPUnit kategori '.$suffix],
            'slug' => 'phpunit-kategori-'.$suffix,
            'active' => true,
        ]);
        $this->size = Size::query()->create([
            'name' => 'STD-'.$suffix,
            'name_i18n' => ['tr' => 'STD-'.$suffix],
            'active' => true,
        ]);
        $this->color = Color::query()->create([
            'name' => 'Siyah-'.$suffix,
            'name_i18n' => ['tr' => 'Siyah-'.$suffix],
            'hex' => '#000000',
            'active' => true,
        ]);

        $this->product = Product::query()->create([
            'category_id' => $this->category->id,
            'code' => 'PHPUNIT-'.$suffix,
            'slug' => 'phpunit-'.$suffix,
            'name' => ['tr' => 'Test Ürün', ...$nameTranslations],
            'description' => ['tr' => 'Test açıklama', ...$descriptionTranslations],
            'price' => 1,
            'currency' => 'TRY',
            'price_try' => 1,
            'price_usd' => 1,
            'price_eur' => 1,
            'pack_size' => 1,
            'stock_status' => 'in_stock',
            'active' => false,
        ]);
        $this->product->images()->create([
            'storage_path' => 'tests/phpunit-product.jpg',
            'alt' => ['tr' => 'PHPUnit ürün görseli'],
            'sort_order' => 0,
            'is_primary' => true,
        ]);
        $this->product->variants()->create([
            'size_id' => $this->size->id,
            'color_id' => $this->color->id,
            'stock_quantity' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        $this->product?->images()->get()->each->delete();
        $this->product?->delete();
        $this->product = null;

        // Oluşturma testinin bıraktığı geçici kayıtlar.
        Product::query()->where('code', 'like', 'PHPUNIT-%')->get()->each(function (Product $product): void {
            $product->images()->get()->each->delete();
            $product->delete();
        });

        $this->size?->delete();
        $this->color?->delete();
        $this->category?->delete();
        Storage::disk(UploadTarget::disk('products'))->delete('tests/phpunit-product.jpg');

        parent::tearDown();
    }

    public function test_it_translates_a_new_record_on_create(): void
    {
        $languages = config('storefront.translation.languages');
        $suffix = uniqid();

        $this->mock(TranslateService::class, function ($mock) use ($languages) {
            $mock->shouldReceive('translateFields')
                ->once()
                ->andReturn([
                    'name' => array_combine($languages, array_map(fn ($lang) => 'name-'.$lang, $languages)),
                    'description' => array_combine($languages, array_map(fn ($lang) => 'desc-'.$lang, $languages)),
                ]);
        });

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'code' => 'PHPUNIT-'.$suffix,
                'slug' => 'phpunit-'.$suffix,
                'name' => ['tr' => 'Yeni Ürün'],
                'description' => ['tr' => 'Yeni açıklama'],
                'price_try' => 100,
                'pack_size' => 1,
                'category_id' => $this->category->id,
                'stock_status' => 'in_stock',
                'active' => false,
                'images' => [[
                    'storage_path' => [UploadedFile::fake()->image('phpunit-product.jpg', 800, 1000)],
                    'alt' => ['tr' => 'Yeni ürün görseli'],
                    'is_primary' => true,
                    'sort_order' => 0,
                ]],
                'variant_size_ids' => [$this->size->id],
                'variant_color_ids' => [$this->color->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = Product::query()->where('code', 'PHPUNIT-'.$suffix)->firstOrFail();

        $this->assertCount(10, $created->name);
        $this->assertSame('Yeni Ürün', $created->name['tr']);
        $this->assertSame('name-en', $created->name['en']);
        $this->assertSame('desc-ar', $created->description['ar']);
    }

    public function test_it_does_not_translate_when_turkish_text_is_unchanged(): void
    {
        $this->mock(TranslateService::class, fn ($mock) => $mock->shouldNotReceive('translateFields'));

        Livewire::test(EditProduct::class, ['record' => $this->product->getKey()])
            ->fillForm(['price_try' => 42])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $this->product->fresh();

        $this->assertSame('42.00', (string) $fresh->price);
        $this->assertSame('Test Ürün', $fresh->name['tr']);
        $this->assertSame('Test Product', $fresh->name['en']);
        $this->assertSame('Test Beschreibung', $fresh->description['de']);
    }

    public function test_it_translates_changed_turkish_text_into_all_locales(): void
    {
        $languages = config('storefront.translation.languages');

        $this->mock(TranslateService::class, function ($mock) use ($languages) {
            $mock->shouldReceive('translateFields')
                ->once()
                ->with(Mockery::on(fn ($fields) => ($fields['name'] ?? null) === 'Yepyeni Ürün'))
                ->andReturn([
                    'name' => array_combine($languages, array_map(fn ($lang) => 'name-'.$lang, $languages)),
                    'description' => array_combine($languages, array_map(fn ($lang) => 'desc-'.$lang, $languages)),
                ]);
        });

        Livewire::test(EditProduct::class, ['record' => $this->product->getKey()])
            ->fillForm([
                'name' => ['tr' => 'Yepyeni Ürün'],
                'description' => ['tr' => 'Yepyeni açıklama'],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $this->product->fresh();

        $this->assertCount(10, $fresh->name);
        $this->assertCount(10, $fresh->description);
        $this->assertSame('Yepyeni Ürün', $fresh->name['tr']);
        $this->assertSame('Yepyeni açıklama', $fresh->description['tr']);

        foreach ($languages as $language) {
            $this->assertSame('name-'.$language, $fresh->name[$language]);
            $this->assertSame('desc-'.$language, $fresh->description[$language]);
        }
    }

    public function test_it_completes_missing_locales_when_turkish_text_is_unchanged(): void
    {
        $languages = config('storefront.translation.languages');

        $this->product->update([
            'name' => ['tr' => 'Test Ürün', 'en' => 'Test Product'],
        ]);

        $this->mock(TranslateService::class, function ($mock) use ($languages) {
            $mock->shouldReceive('translateFields')
                ->once()
                ->with(Mockery::on(fn ($fields) => array_keys($fields) === ['name']
                    && $fields['name'] === 'Test Ürün'))
                ->andReturn([
                    'name' => array_combine($languages, array_map(fn ($lang) => 'name-'.$lang, $languages)),
                ]);
        });

        Livewire::test(EditProduct::class, ['record' => $this->product->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $this->product->fresh();

        $this->assertSame('Test Ürün', $fresh->name['tr']);

        foreach ($languages as $language) {
            $this->assertSame('name-'.$language, $fresh->name[$language]);
        }
    }

    public function test_it_saves_turkish_and_warns_when_translation_fails(): void
    {
        $this->mock(TranslateService::class, function ($mock) {
            $mock->shouldReceive('translateFields')->once()->andThrow(new RuntimeException('Gemini patladı.'));
        });

        Livewire::test(EditProduct::class, ['record' => $this->product->getKey()])
            ->fillForm(['name' => ['tr' => 'Hatalı Çeviri Ürünü']])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Otomatik çeviri yapılamadı, metinler Türkçe kaydedildi.');

        $fresh = $this->product->fresh();

        $this->assertSame('Hatalı Çeviri Ürünü', $fresh->name['tr']);
        // Eski çeviriler korunur.
        $this->assertSame('Test Product', $fresh->name['en']);
        $this->assertSame('Test Produkt', $fresh->name['de']);
    }
}
