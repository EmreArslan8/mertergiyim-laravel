<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Kök adres varsayılan dile yönlenir.
     */
    public function test_the_root_redirects_to_the_default_locale(): void
    {
        $this->get('/')->assertRedirect('/'.config('storefront.default_locale'));
    }

    public function test_sitemap_and_robots_are_served(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.config('storefront.site_url').'/tr</loc>', escape: false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.config('storefront.site_url').'/sitemap.xml');
    }
}
