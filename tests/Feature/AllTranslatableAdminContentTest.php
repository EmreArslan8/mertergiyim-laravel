<?php

namespace Tests\Feature;

use App\Filament\Resources\BlogPosts\Pages\ListBlogPosts;
use App\Filament\Resources\Colors\Pages\CreateColor;
use App\Filament\Resources\ContentPages\Pages\ListContentPages;
use App\Filament\Resources\HeroSlides\Pages\CreateHeroSlide;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\SiteSettings\Pages\EditSiteSetting;
use App\Filament\Resources\Sizes\Pages\CreateSize;
use App\Models\BlogPost;
use App\Models\Color;
use App\Models\ContentPage;
use App\Models\HeroSlide;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SiteSetting;
use App\Models\Size;
use App\Models\User;
use App\Services\TranslateService;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class AllTranslatableAdminContentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
    }

    public function test_new_size_is_translated_into_every_locale(): void
    {
        $this->mockTranslations(['name_i18n' => 'E2E Beden']);

        Livewire::test(CreateSize::class)
            ->fillForm([
                'name_i18n' => ['tr' => 'E2E Beden'],
                'sort_order' => 99,
                'active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTranslated(Size::query()->where('name', 'E2E Beden')->firstOrFail(), ['name_i18n']);
    }

    public function test_new_color_is_translated_into_every_locale(): void
    {
        $this->mockTranslations(['name_i18n' => 'E2E Renk']);

        Livewire::test(CreateColor::class)
            ->fillForm([
                'name_i18n' => ['tr' => 'E2E Renk'],
                'hex' => '#123456',
                'sort_order' => 99,
                'active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTranslated(Color::query()->where('name', 'E2E Renk')->firstOrFail(), ['name_i18n']);
    }

    public function test_new_hero_slide_is_translated_into_every_locale(): void
    {
        $this->mockTranslations([
            'title' => 'E2E Yaz Koleksiyonu',
            'button_text' => 'Şimdi İncele',
        ]);

        Livewire::test(CreateHeroSlide::class)
            ->fillForm([
                'image_path' => UploadedFile::fake()->image('e2e-hero.jpg', 1200, 700),
                'title' => ['tr' => 'E2E Yaz Koleksiyonu'],
                'button_text' => ['tr' => 'Şimdi İncele'],
                'button_url' => '/tr#urunler',
                'sort_order' => 99,
                'active' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $slide = HeroSlide::query()
            ->where('sort_order', 99)
            ->whereJsonContains('title->tr', 'E2E Yaz Koleksiyonu')
            ->firstOrFail();

        $this->assertTranslated($slide, ['title', 'button_text']);
    }

    public function test_new_content_page_is_translated_into_every_locale(): void
    {
        $this->mockTranslations([
            'title' => 'E2E Teslimat Rehberi',
            'content' => 'E2E teslimat içeriği',
            'seo_title' => 'E2E Teslimat',
            'seo_description' => 'E2E teslimat açıklaması',
        ]);

        Livewire::test(ListContentPages::class)
            ->callAction(CreateAction::class, data: [
                'title' => ['tr' => 'E2E Teslimat Rehberi'],
                'slug' => 'e2e-teslimat-rehberi',
                'content' => ['tr' => 'E2E teslimat içeriği'],
                'seo_title' => ['tr' => 'E2E Teslimat'],
                'seo_description' => ['tr' => 'E2E teslimat açıklaması'],
                'sort_order' => 99,
                'active' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertTranslated(
            ContentPage::query()->where('slug', 'e2e-teslimat-rehberi')->firstOrFail(),
            ['title', 'content', 'seo_title', 'seo_description'],
        );
    }

    public function test_new_blog_post_is_translated_into_every_locale(): void
    {
        $this->mockTranslations([
            'title' => 'E2E Yaz Modası',
            'excerpt' => 'E2E blog özeti',
            'content' => 'E2E blog içeriği',
        ]);

        Livewire::test(ListBlogPosts::class)
            ->callAction(CreateAction::class, data: [
                'title' => ['tr' => 'E2E Yaz Modası'],
                'slug' => 'e2e-yaz-modasi',
                'excerpt' => ['tr' => 'E2E blog özeti'],
                'content' => ['tr' => 'E2E blog içeriği'],
                'published_at' => now(),
                'active' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertTranslated(
            BlogPost::query()->where('slug', 'e2e-yaz-modasi')->firstOrFail(),
            ['title', 'excerpt', 'content'],
        );
    }

    public function test_new_media_record_is_translated_into_every_locale(): void
    {
        $this->mockTranslations([
            'title' => 'E2E Koleksiyon Görseli',
            'alt' => 'E2E elbise görseli',
            'caption' => 'E2E medya açıklaması',
        ]);

        Livewire::test(ListMedia::class)
            ->callAction(CreateAction::class, data: [
                'type' => 'image',
                'file_path' => UploadedFile::fake()->image('e2e-media.jpg', 800, 1000),
                'title' => ['tr' => 'E2E Koleksiyon Görseli'],
                'alt' => ['tr' => 'E2E elbise görseli'],
                'caption' => ['tr' => 'E2E medya açıklaması'],
                'sort_order' => 99,
                'active' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertTranslated(
            Media::query()->where('sort_order', 99)->firstOrFail(),
            ['title', 'alt', 'caption'],
        );
    }

    public function test_new_product_image_alt_text_is_translated_into_every_locale(): void
    {
        $this->mockTranslations([
            'alt' => 'E2E kırmızı elbise görseli',
        ]);

        $product = Product::query()->firstOrFail();

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => EditProduct::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'storage_path' => UploadedFile::fake()->image('e2e-product.jpg', 800, 1000),
                'alt' => ['tr' => 'E2E kırmızı elbise görseli'],
                'sort_order' => 99,
                'is_primary' => false,
            ])
            ->assertHasNoActionErrors();

        $this->assertTranslated(
            ProductImage::query()
                ->where('product_id', $product->getKey())
                ->where('sort_order', 99)
                ->firstOrFail(),
            ['alt'],
        );
    }

    public function test_changed_site_settings_are_translated_into_every_locale(): void
    {
        $this->mockTranslations([
            'footerInfoTitle' => 'E2E Bilgilendirmeler',
            'footerDescription' => 'E2E footer açıklaması',
        ]);

        $setting = SiteSetting::query()->whereKey('storefront')->firstOrFail();

        Livewire::test(EditSiteSetting::class, ['record' => $setting->getKey()])
            ->fillForm([
                'value' => [
                    'tr' => [
                        'footerInfoTitle' => 'E2E Bilgilendirmeler',
                        'footerDescription' => 'E2E footer açıklaması',
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $setting->fresh();

        $turkish = [
            'footerInfoTitle' => 'E2E Bilgilendirmeler',
            'footerDescription' => 'E2E footer açıklaması',
        ];

        foreach ($turkish as $field => $value) {
            $this->assertSame($value, $fresh->value['tr'][$field]);

            foreach (config('storefront.translation.languages') as $locale) {
                $this->assertSame($field.'-'.$locale, $fresh->value[$locale][$field]);
            }
        }
    }

    /**
     * @param  array<string, string>  $source
     */
    private function mockTranslations(array $source): void
    {
        $languages = config('storefront.translation.languages');
        $translated = [];

        foreach (array_keys($source) as $field) {
            $translated[$field] = array_combine(
                $languages,
                array_map(fn (string $locale): string => $field.'-'.$locale, $languages),
            );
        }

        $this->mock(TranslateService::class, function ($mock) use ($source, $translated): void {
            $mock->shouldReceive('translateFields')
                ->once()
                ->with($source)
                ->andReturn($translated);
        });
    }

    /**
     * @param  array<int, string>  $fields
     */
    private function assertTranslated(Model $record, array $fields): void
    {
        foreach ($fields as $field) {
            $value = $record->getAttribute($field);

            $this->assertCount(10, $value, $field.' tam 10 dil içermiyor.');
            $this->assertNotEmpty($value['tr'] ?? null);

            foreach (config('storefront.translation.languages') as $locale) {
                $this->assertSame($field.'-'.$locale, $value[$locale] ?? null);
            }
        }
    }
}
