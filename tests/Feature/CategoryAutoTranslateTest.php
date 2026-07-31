<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;
use App\Services\TranslateService;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryAutoTranslateTest extends TestCase
{
    public function test_category_name_is_translated_and_slug_is_generated_automatically(): void
    {
        $this->actingAs(User::query()->firstOrFail());
        $languages = config('storefront.translation.languages');

        $this->mock(TranslateService::class, function ($mock) use ($languages) {
            $mock->shouldReceive('translateFields')
                ->once()
                ->with(['name_i18n' => 'Yazlık Elbiseler'])
                ->andReturn([
                    'name_i18n' => array_combine(
                        $languages,
                        array_map(fn ($language) => 'category-'.$language, $languages),
                    ),
                ]);
        });

        // Kategori ekleme ayrı sayfada değil, liste üzerindeki pencerede.
        Livewire::test(ListCategories::class)
            ->callAction('create', data: [
                'name_i18n' => ['tr' => 'Yazlık Elbiseler'],
                'active' => true,
            ])
            ->assertHasNoActionErrors();

        $category = Category::query()->where('slug', 'yazlik-elbiseler')->firstOrFail();

        $this->assertSame('Yazlık Elbiseler', $category->name);
        $this->assertSame('Yazlık Elbiseler', $category->name_i18n['tr']);
        $this->assertSame('category-en', $category->name_i18n['en']);
        $this->assertCount(10, $category->name_i18n);

        $category->delete();
    }
}
