<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\ContentPage;
use App\Models\HeroSlide;
use App\Models\MediaPost;
use App\Models\Product;
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

    public function test_dashboard_renders_summary_and_quick_actions(): void
    {
        $this->actingAs(User::query()->firstOrFail())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Yönetim özeti')
            ->assertSee('Yeni ürün')
            ->assertSee('Kısayollar')
            ->assertDontSee('Çeviri Kullanımı')
            ->assertDontSee('Siteyi görüntüle');
    }

    #[DataProvider('panelPages')]
    public function test_panel_pages_render(string $path): void
    {
        $admin = User::query()->firstOrFail();

        $response = $this->actingAs($admin)->get($path);

        if ($response->isRedirect()) {
            $this->get($response->headers->get('Location'))->assertOk();
        } else {
            $response->assertOk();
        }
    }

    public static function panelPages(): array
    {
        return array_map(fn ($path) => [$path], [
            '/admin',
            '/admin/products',
            '/admin/products/create',
            '/admin/categories',
            '/admin/sizes',
            '/admin/colors',
            '/admin/currencies',
            '/admin/languages',
            '/admin/hero-slides',
            '/admin/hero-slides/create',
            '/admin/site-links',
            '/admin/site-settings',
            '/admin/orders',
            '/admin/orders/create',
            '/admin/content-pages',
            '/admin/content-pages/create',
            '/admin/blog-posts',
            '/admin/blog-posts/create',
            '/admin/media',
            '/admin/media/create',
            '/admin/admin-users',
            '/admin/admin-users/create',
        ]);
    }

    /**
     * Düzenleme sayfaları relation manager'ları da render ediyor mu?
     *
     * Renk/beden/kategori/para birimi/dil/site linki kaynaklarında ayrı
     * düzenleme sayfası yok; bunlar liste üzerindeki pencerede düzenleniyor.
     * (Ürün görsellerindeki "Alternatif metin" alanı buradan geçiyor.)
     */
    public function test_edit_pages_render(): void
    {
        $this->actingAs(User::query()->firstOrFail());

        $paths = array_filter([
            ($id = Product::query()->value('id')) ? '/admin/products/'.$id.'/edit' : null,
            ($id = HeroSlide::query()->value('id')) ? '/admin/hero-slides/'.$id.'/edit' : null,
            ($id = ContentPage::query()->value('id')) ? '/admin/content-pages/'.$id.'/edit' : null,
            ($id = BlogPost::query()->value('id')) ? '/admin/blog-posts/'.$id.'/edit' : null,
            ($id = MediaPost::query()->value('id')) ? '/admin/media/'.$id.'/edit' : null,
            ($key = SiteSetting::query()->value('key')) ? '/admin/site-settings/'.$key.'/edit' : null,
            ($id = User::query()->value('id')) ? '/admin/admin-users/'.$id.'/edit' : null,
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
