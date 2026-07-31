<?php

namespace Tests\Feature;

use App\Filament\Resources\SiteLinks\Pages\ListSiteLinks;
use App\Models\SiteLink;
use App\Models\User;
use App\Services\TranslateService;
use App\Support\TranslationStatus;
use Livewire\Livewire;
use Tests\TestCase;

class SiteLinkAutoTranslateTest extends TestCase
{
    public function test_seeded_header_links_have_every_locale(): void
    {
        $links = SiteLink::query()->where('location', 'header')->get();

        $this->assertNotEmpty($links);

        foreach ($links as $link) {
            $this->assertSame(
                [],
                TranslationStatus::missingLocales($link->label),
                "Header linkinde eksik çeviri var: {$link->link_key}",
            );
        }
    }

    public function test_new_footer_link_is_translated_automatically(): void
    {
        $this->actingAs(User::query()->firstOrFail());
        $languages = config('storefront.translation.languages');

        $this->mock(TranslateService::class, function ($mock) use ($languages) {
            $mock->shouldReceive('translateFields')
                ->once()
                ->with(['label' => 'Mağazalarımız'])
                ->andReturn([
                    'label' => array_combine(
                        $languages,
                        array_map(fn ($language) => 'footer-'.$language, $languages),
                    ),
                ]);
        });

        Livewire::test(ListSiteLinks::class)
            ->callAction('create', data: [
                'location' => 'footer',
                'link_key' => 'phpunit-stores',
                'url' => '/magazalarimiz',
                'sort_order' => 99,
                'active' => true,
                'label' => ['tr' => 'Mağazalarımız'],
            ])
            ->assertHasNoActionErrors();

        $link = SiteLink::query()
            ->where('location', 'footer')
            ->where('link_key', 'phpunit-stores')
            ->firstOrFail();

        $this->assertSame('Mağazalarımız', $link->label['tr']);
        $this->assertSame('footer-en', $link->label['en']);
        $this->assertCount(10, $link->label);

        $link->delete();
    }
}
