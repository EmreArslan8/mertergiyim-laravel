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
        $imageAltTranslations = array_fill_keys($languages, 'PHPUnit product image');
        $nameTranslations['de'] = 'Test Produkt';
        $descriptionTranslations['de'] = 'Test Beschreibung';
        $imageAltTranslations['de'] = 'PHPUnit Produktbild';
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
            'stock_status' => true,
            'active' => false,
        ]);
        $this->product->images()->create([
            'storage_path' => 'tests/phpunit-product.jpg',
            'alt' => ['tr' => 'PHPUnit ürün görseli', ...$imageAltTranslations],
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
                ->with(Mockery::on(fn ($fields) => ($fields['name'] ?? null) === 'Yeni Ürün'
                    && ($fields['description'] ?? null) === '<p>Yeni açıklama</p>'))
                ->andReturn([
                    'name' => array_combine($languages, array_map(fn ($lang) => 'name-'.$lang, $languages)),
                    'description' => array_combine($languages, array_map(fn ($lang) => 'desc-'.$lang, $languages)),
                ]);
            $mock->shouldReceive('translateFields')
                ->once()
                ->with([
                    'alt' => 'Yeni ürün görseli',
                    'alt_2' => 'Yeni ürün arka görseli',
                ])
                ->andReturn([
                    'alt' => array_combine($languages, array_map(fn ($lang) => 'alt-'.$lang, $languages)),
                    'alt_2' => array_combine($languages, array_map(fn ($lang) => 'alt-2-'.$lang, $languages)),
                ]);
        });

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => ['tr' => 'Yeni Ürün'],
                'description' => ['tr' => 'Yeni açıklama'],
                'price_usd' => 100,
                'pack_size' => 1,
                'category_id' => $this->category->id,
                'stock_status' => true,
                'active' => false,
                'images' => [
                    [
                        'storage_path' => [UploadedFile::fake()->image('phpunit-product.jpg', 800, 1000)],
                        'alt' => ['tr' => 'Yeni ürün görseli'],
                        'is_primary' => true,
                        'sort_order' => 0,
                    ],
                    [
                        'storage_path' => [UploadedFile::fake()->image('phpunit-product-back.jpg', 800, 1000)],
                        'alt' => ['tr' => 'Yeni ürün arka görseli'],
                        'is_primary' => false,
                        'sort_order' => 1,
                    ],
                ],
                'variant_size_ids' => [$this->size->id],
                'variant_color_ids' => [$this->color->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Kodu sistem atar; ürün adından bulunur.
        $created = Product::query()->where('name_key', 'yeni-urun')->firstOrFail();

        $this->assertCount(10, $created->name);
        $this->assertSame('Yeni Ürün', $created->name['tr']);
        $this->assertSame('name-en', $created->name['en']);
        $this->assertSame('desc-ar', $created->description['ar']);

        $this->assertCount(2, $created->images);
        $imageAlt = $created->images[0]->alt;
        $this->assertCount(10, $imageAlt);
        $this->assertSame('Yeni ürün görseli', $imageAlt['tr']);
        $this->assertSame('alt-de', $imageAlt['de']);
        $this->assertSame('alt-2-en', $created->images[1]->alt['en']);
    }

    public function test_it_does_not_translate_when_turkish_text_is_unchanged(): void
    {
        $this->mock(TranslateService::class, fn ($mock) => $mock->shouldNotReceive('translateFields'));

        Livewire::test(EditProduct::class, ['record' => $this->product->getKey()])
            ->fillForm(['price_usd' => 42])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $this->product->fresh();

        $this->assertSame('42.00', (string) $fresh->price_usd);
        $this->assertSame('USD', $fresh->currency);
        $this->assertSame('Test Ürün', $fresh->name['tr']);
        $this->assertSame('Test Product', $fresh->name['en']);
        $this->assertSame('Test Beschreibung', $fresh->description['de']);
    }

    public function test_image_alt_translation_failure_does_not_block_the_record(): void
    {
        $this->mock(TranslateService::class, function ($mock) {
            $mock->shouldReceive('translateFields')
                ->once()
                ->with(['alt' => 'Yeni görsel'])
                ->andThrow(new RuntimeException('Gemini geçici olarak kullanılamıyor.'));
        });

        $component = Livewire::test(EditProduct::class, ['record' => $this->product->getKey()])
            ->set('data.images', [
                ['alt' => ['tr' => 'Yeni görsel']],
            ]);

        $result = $component->instance()->fillAutomaticImageAltTranslations(
            ['alt' => ['tr' => 'Yeni görsel']],
            null,
        );

        $this->assertSame(['tr' => 'Yeni görsel'], $result['alt']);
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
        // Açıklama zengin editörle giriliyor: düz metin paragrafa sarılır.
        $this->assertSame('<p>Yepyeni açıklama</p>', $fresh->description['tr']);

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

    public function test_it_rolls_back_and_warns_when_translation_fails(): void
    {
        $this->mock(TranslateService::class, function ($mock) {
            $mock->shouldReceive('translateFields')->once()->andThrow(new RuntimeException('Gemini patladı.'));
        });

        Livewire::test(EditProduct::class, ['record' => $this->product->getKey()])
            ->fillForm(['name' => ['tr' => 'Hatalı Çeviri Ürünü']])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Kayıt yapılmadı: otomatik çeviri başarısız.');

        $fresh = $this->product->fresh();

        $this->assertSame('Test Ürün', $fresh->name['tr']);
        // İşlem tamamen geri alınır, mevcut çeviriler de korunur.
        $this->assertSame('Test Product', $fresh->name['en']);
        $this->assertSame('Test Produkt', $fresh->name['de']);
    }
}
