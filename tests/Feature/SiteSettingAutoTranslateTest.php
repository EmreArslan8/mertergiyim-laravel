<?php

namespace Tests\Feature;

use App\Filament\Resources\SiteSettings\Pages\EditSiteSetting;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\TranslateService;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * site_settings.value jsonb yapısı { locale: { alan: değer } } olduğu için
 * kaydetme sırasında diğer dillerin kaybolmadığını doğrular.
 *
 * Türkçe metinler değiştirilmediği için çeviri çağrısı beklenmez ve kayıt
 * içeriği aynı kalır; yine de orijinal değer tedbiren geri yazılır.
 */
class SiteSettingAutoTranslateTest extends TestCase
{
    private ?SiteSetting $setting = null;

    private ?array $original = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());

        $this->setting = SiteSetting::query()->firstOrFail();
        $this->original = $this->setting->value;
    }

    protected function tearDown(): void
    {
        if ($this->setting && $this->original !== null) {
            $this->setting->newQuery()
                ->whereKey($this->setting->getKey())
                ->update(['value' => json_encode($this->original, JSON_UNESCAPED_UNICODE)]);
        }

        $this->setting = null;
        $this->original = null;

        parent::tearDown();
    }

    public function test_saving_without_changes_keeps_every_locale_and_skips_translation(): void
    {
        $this->mock(TranslateService::class, fn ($mock) => $mock->shouldNotReceive('translateFields'));

        Livewire::test(EditSiteSetting::class, ['record' => $this->setting->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $this->setting->fresh();

        foreach ($this->original as $locale => $fields) {
            foreach ($fields as $field => $value) {
                $this->assertSame($value, $fresh->value[$locale][$field] ?? null, "value.{$locale}.{$field} kayboldu");
            }
        }
    }
}
