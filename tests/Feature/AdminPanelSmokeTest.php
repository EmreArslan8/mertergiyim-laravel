<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\Product;
use App\Models\SiteLink;
use App\Models\SiteSetting;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Panel sayfaları canlı Supabase verisine karşı açılıyor mu?
 *
 * Yalnızca GET istekleri yapılır; hiçbir kayıt oluşturulmaz/değiştirilmez.
 */
class AdminPanelSmokeTest extends TestCase
{
    public function test_login_page_is_reachable(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    #[DataProvider('panelPages')]
    public function test_panel_pages_render(string $path): void
    {
        $admin = User::query()->firstOrFail();

        $this->actingAs($admin)->get($path)->assertOk();
    }

    public static function panelPages(): array
    {
        return array_map(fn ($path) => [$path], [
            '/admin',
            '/admin/products',
            '/admin/products/create',
            '/admin/categories',
            '/admin/categories/create',
            '/admin/sizes',
            '/admin/colors',
            '/admin/currencies',
            '/admin/languages',
            '/admin/hero-slides',
            '/admin/hero-slides/create',
            '/admin/site-links',
            '/admin/site-links/create',
            '/admin/site-settings',
            '/admin/orders',
            '/admin/orders/create',
            '/admin/translation-usages',
            '/admin/content-pages',
            '/admin/blog-posts',
            '/admin/media',
            '/admin/admin-users',
        ]);
    }

    /**
     * Düzenleme sayfaları relation manager'ları da render ediyor mu?
     * (Ürün görsellerindeki "Alternatif metin" alanı buradan geçiyor.)
     */
    public function test_edit_pages_render(): void
    {
        $this->actingAs(User::query()->firstOrFail());

        $paths = array_filter([
            ($id = Product::query()->value('id')) ? '/admin/products/'.$id.'/edit' : null,
            ($id = HeroSlide::query()->value('id')) ? '/admin/hero-slides/'.$id.'/edit' : null,
            ($id = SiteLink::query()->value('id')) ? '/admin/site-links/'.$id.'/edit' : null,
            ($key = SiteSetting::query()->value('key')) ? '/admin/site-settings/'.$key.'/edit' : null,
        ]);

        $this->assertNotEmpty($paths);

        foreach ($paths as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_storefront_still_renders(): void
    {
        foreach (['/tr', '/ar', '/tr/siparis-takibi'] as $path) {
            $this->get($path)->assertOk();
        }
    }
}
