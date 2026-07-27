<?php

namespace Tests\Feature;

use App\Filament\Resources\SiteLinks\Pages\CreateSiteLink;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Ürün / hero slide / site ayarları formlarında artık yalnızca Türkçe alan var
 * (çeviri kayıt anında otomatik; bkz. AutoTranslateOnSaveTest). Manuel "Çevir"
 * aksiyonu yalnızca site bağlantılarında duruyor; testi oraya taşındı.
 *
 * Gerçek Gemini çağrısı yapar; hiçbir kayıt oluşturulmaz.
 */
class TranslateActionTest extends TestCase
{
    public function test_translate_action_fills_the_other_locales(): void
    {
        $this->actingAs(User::query()->firstOrFail());

        $component = Livewire::test(CreateSiteLink::class)
            ->fillForm(['label' => ['tr' => 'Ürünler']])
            ->callAction(TestAction::make('translate')->schemaComponent('translate_actions'));

        $data = $component->get('data');

        foreach (config('storefront.translation.languages') as $language) {
            $this->assertNotEmpty($data['label'][$language] ?? null, "label.{$language} boş kaldı");
        }

        $this->assertSame('Ürünler', $data['label']['tr']);
    }
}
