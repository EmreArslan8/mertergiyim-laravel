<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Widgets\StorefrontStatsWidget;
use App\Support\BrandSettings;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /** Tarayıcı önbelleğini kırmak için tema sürümü. */
    public const THEME_VERSION = '89';

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // Panelin açılışı ve giriş sonrası yönlendirme: kontrol paneli yerine
            // ürün listesi. Dashboard sayfası duruyor (topbar bağlantısı + widget).
            ->homeUrl(fn (): string => ProductResource::getUrl('index'))
            // Kullanıcı menü öğesinin üzerine geldiğinde hedef sayfayı önceden
            // getirir; uzak veritabanı gecikmesini tıklamadan önce başlatır.
            ->spa(hasPrefetching: true)
            ->brandName(fn (): string => BrandSettings::name('tr'))
            ->brandLogo(fn (): ?string => BrandSettings::logoUrl())
            ->brandLogoHeight('2.25rem')
            ->favicon(fn (): string => BrandSettings::faviconUrl() ?? asset('icon.png'))
            // Temel genişlik; merter-admin.css bunu ekran boyuna göre ezer
            // (1024–1279px: 14rem, 1280+: 16rem, 1536+: 17rem, mobil drawer: 17.5rem).
            ->sidebarWidth('16rem')
            ->maxContentWidth(Width::Full)
            ->colors([
                'primary' => Color::hex('#171717'),
                'gray' => Color::Stone,
            ])
            ->darkMode(false)
            ->userMenu(false)
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => '<link rel="stylesheet" href="'.asset('css/merter-admin.css').'?v='.static::THEME_VERSION.'" data-navigate-track />',
            )
            ->renderHook(
                // Sortable, merter-admin.js'ten önce yüklenmeli: sürükle-bırak
                // sıralama (Hızlı Ekle görselleri) global window.Sortable'a bakar.
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): string => '<script src="'.asset('js/sortable.min.js').'?v=1.15.6" data-navigate-track></script>'
                    .'<script src="'.asset('js/merter-admin.js').'?v='.static::THEME_VERSION.'" data-navigate-track></script>',
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): string => view('filament.sidebar-brand')->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => view('filament.topbar-section')->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => view('filament.topbar-user')->render(),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                StorefrontStatsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
