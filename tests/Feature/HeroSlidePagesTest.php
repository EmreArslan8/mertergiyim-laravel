<?php

namespace Tests\Feature;

use App\Filament\Resources\HeroSlides\HeroSlideResource;
use App\Filament\Resources\HeroSlides\Pages\EditHeroSlide;
use App\Filament\Resources\HeroSlides\Pages\ListHeroSlides;
use App\Models\HeroSlide;
use App\Models\User;
use App\Support\UploadTarget;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Slider düzenleme modalda değil kendi sayfasında açılır.
 */
class HeroSlidePagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
    }

    private function slide(): HeroSlide
    {
        // FileUpload kaydı açarken dosyanın diskte olup olmadığına bakıyor;
        // yoksa alanı boşaltıp `required` doğrulamasını düşürüyor.
        Storage::disk(UploadTarget::disk('site'))->put('hero/test.webp', 'x');

        return HeroSlide::query()->create([
            'title' => ['tr' => 'Test başlık'],
            'button_text' => ['tr' => 'İncele'],
            'button_url' => '/#urunler',
            'image_path' => 'hero/test.webp',
            'sort_order' => 0,
            'active' => true,
        ]);
    }

    public function test_rows_link_to_the_edit_page(): void
    {
        $slide = $this->slide();

        Livewire::test(ListHeroSlides::class)
            ->assertOk()
            ->assertSee(HeroSlideResource::getUrl('edit', ['record' => $slide]), escape: false);
    }

    public function test_edit_page_opens_and_saves(): void
    {
        $slide = $this->slide();

        Livewire::test(EditHeroSlide::class, ['record' => $slide->getKey()])
            ->assertOk()
            ->assertSee('Görsel')
            ->assertSee('Yayın')
            ->fillForm(['sort_order' => 5, 'active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $slide->refresh();

        $this->assertSame(5, (int) $slide->sort_order);
        $this->assertFalse((bool) $slide->active);
    }
}
