<?php

namespace Tests\Feature;

use App\Filament\Resources\HeroSlides\Pages\CreateHeroSlide;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Formdaki "Çevir" aksiyonu tr değerini alıp 9 dili dolduruyor mu?
 *
 * Gerçek Gemini çağrısı yapar; hiçbir kayıt oluşturulmaz.
 */
class TranslateActionTest extends TestCase
{
    public function test_translate_action_fills_the_other_locales(): void
    {
        $this->actingAs(User::query()->firstOrFail());

        $component = Livewire::test(CreateHeroSlide::class)
            ->fillForm([
                'title' => ['tr' => 'Yeni Sezon'],
                'button_text' => ['tr' => 'Ürünleri Gör'],
            ])
            ->callAction(TestAction::make('translate')->schemaComponent('translate_actions'));

        $data = $component->get('data');

        foreach (config('storefront.translation.languages') as $language) {
            $this->assertNotEmpty($data['title'][$language] ?? null, "title.{$language} boş kaldı");
            $this->assertNotEmpty($data['button_text'][$language] ?? null, "button_text.{$language} boş kaldı");
        }

        $this->assertSame('Yeni Sezon', $data['title']['tr']);
    }
}
