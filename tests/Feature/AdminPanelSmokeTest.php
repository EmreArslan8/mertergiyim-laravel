<?php

namespace Tests\Feature;

use App\Models\User;
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

    /**
     * @dataProvider panelPages
     */
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
        ]);
    }

    public function test_storefront_still_renders(): void
    {
        foreach (['/tr', '/ar', '/tr/siparis-takibi'] as $path) {
            $this->get($path)->assertOk();
        }
    }
}
