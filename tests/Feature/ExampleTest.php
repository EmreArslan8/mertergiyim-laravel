<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\SiteLink;
use Illuminate\Support\Facades\Cache;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Varsayılan dil kökte yaşar; ön ekli sürümü köke yönlenir.
     */
    public function test_the_default_locale_is_served_at_the_root(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_homepage_renders_every_active_hero_as_an_autoplay_slide(): void
    {
        $suffix = uniqid();
        $slides = collect([1, 2])->map(fn (int $number) => HeroSlide::query()->create([
            'title' => ['tr' => "Autoplay Hero {$number} {$suffix}"],
            'button_text' => ['tr' => 'İncele'],
            'button_url' => '/urunler',
            'image_path' => "tests/hero-{$number}-{$suffix}.jpg",
            'sort_order' => 900 + $number,
            'active' => true,
        ]));
        Cache::forget('storefront:home');

        try {
            $response = $this->get('/')->assertOk();

            $response
                ->assertSee('data-hero-slider', false)
                ->assertSee('data-autoplay="6000"', false)
                ->assertSee("Autoplay Hero 1 {$suffix}")
                ->assertSee("Autoplay Hero 2 {$suffix}")
                ->assertSee('data-hero-next', false)
                ->assertSee('data-hero-previous', false);
        } finally {
            $slides->each->delete();
            Cache::forget('storefront:home');
        }
    }

    public function test_the_default_locale_prefix_redirects_to_the_root(): void
    {
        $this->get('/'.config('storefront.default_locale'))->assertRedirect('/');
        $this->get('/'.config('storefront.default_locale').'/iletisim')->assertRedirect('/iletisim');
    }

    public function test_other_locales_keep_their_prefix(): void
    {
        $this->get('/en')->assertOk();
    }

    public function test_desktop_header_visibility_is_controlled_by_the_panel_active_setting(): void
    {
        SiteLink::query()->updateOrCreate(
            ['location' => 'header', 'link_key' => 'blog'],
            ['label' => ['tr' => 'Blog'], 'url' => '/blog', 'sort_order' => 90, 'active' => true],
        );
        SiteLink::query()->updateOrCreate(
            ['location' => 'header', 'link_key' => 'hidden-test'],
            ['label' => ['tr' => 'Gizli Menü'], 'url' => '/gizli', 'sort_order' => 91, 'active' => false],
        );
        Cache::forget('storefront:chrome');

        $this->get('/')
            ->assertOk()
            ->assertSee('href="/blog"', escape: false)
            ->assertSee('Blog')
            ->assertDontSee('Gizli Menü');
    }

    public function test_sitemap_and_robots_are_served(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.config('storefront.site_url').'/</loc>', escape: false)
            ->assertSee('<loc>'.config('storefront.site_url').'/en</loc>', escape: false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.config('storefront.site_url').'/sitemap.xml');
    }
}
