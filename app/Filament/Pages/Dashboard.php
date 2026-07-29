<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AdminUsers\AdminUserResource;
use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\ContentPages\ContentPageResource;
use App\Filament\Resources\HeroSlides\HeroSlideResource;
use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\SiteSettings\SiteSettingResource;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    protected function getViewData(): array
    {
        $summary = Cache::remember(
            'admin:dashboard:summary',
            now()->addSeconds(20),
            fn () => DB::selectOne('
                select
                    (select count(*) from products) as products,
                    (select count(*) from products where active = true) as active_products,
                    (select count(*) from orders) as orders,
                    (select count(*) from orders where status = ?) as new_orders,
                    (select count(*) from contact_messages) as messages,
                    (select count(*) from contact_messages where read = false) as unread_messages,
                    (select count(*) from categories) as categories,
                    (select count(*) from hero_slides) as slides,
                    (select count(*) from hero_slides where active = true) as active_slides
            ', ['new']),
        );

        $user = filament()->auth()->user();
        $name = trim((string) ($user?->name ?: $user?->email));
        $name = Str::before($name, ' ');

        return [
            'welcomeName' => $name ?: 'Yönetici',
            'today' => now()->translatedFormat('d F Y, H:i'),
            'stats' => array_values(array_filter([
                ProductResource::canAccess() ? [
                    'label' => 'Ürünler',
                    'value' => (string) $summary->products,
                    'detail' => $summary->active_products.' yayında',
                    'icon' => 'heroicon-o-shopping-bag',
                    'url' => ProductResource::getUrl('index'),
                ] : null,
                OrderResource::canAccess() ? [
                    'label' => 'Siparişler',
                    'value' => $summary->new_orders.' / '.$summary->orders,
                    'detail' => 'Yeni / toplam',
                    'icon' => 'heroicon-o-clipboard-document-list',
                    'url' => OrderResource::getUrl('index'),
                    'alert' => (int) $summary->new_orders > 0,
                ] : null,
                ContactMessageResource::canAccess() ? [
                    'label' => 'Mesajlar',
                    'value' => $summary->unread_messages.' / '.$summary->messages,
                    'detail' => 'Okunmamış / toplam',
                    'icon' => 'heroicon-o-envelope',
                    'url' => ContactMessageResource::getUrl('index'),
                    'alert' => (int) $summary->unread_messages > 0,
                ] : null,
                CategoryResource::canAccess() ? [
                    'label' => 'Kategoriler',
                    'value' => (string) $summary->categories,
                    'detail' => 'Toplam kategori',
                    'icon' => 'heroicon-o-rectangle-stack',
                    'url' => CategoryResource::getUrl('index'),
                ] : null,
                HeroSlideResource::canAccess() ? [
                    'label' => 'Slider',
                    'value' => (string) $summary->active_slides,
                    'detail' => $summary->slides.' toplam slider',
                    'icon' => 'heroicon-o-photo',
                    'url' => HeroSlideResource::getUrl('index'),
                ] : null,
            ])),
            'quickActions' => array_values(array_filter([
                ProductResource::canCreate() ? [
                    'label' => 'Yeni ürün',
                    'description' => 'Kataloğa ürün ekle',
                    'icon' => 'heroicon-o-plus',
                    'url' => ProductResource::getUrl('create'),
                    'primary' => true,
                ] : null,
                OrderResource::canCreate() ? [
                    'label' => 'Yeni sipariş',
                    'description' => 'Manuel sipariş oluştur',
                    'icon' => 'heroicon-o-plus',
                    'url' => OrderResource::getUrl('create'),
                ] : null,
                CategoryResource::canCreate() ? [
                    'label' => 'Yeni kategori',
                    'description' => 'Katalog yapısını düzenle',
                    'icon' => 'heroicon-o-plus',
                    'url' => CategoryResource::getUrl('create'),
                ] : null,
                HeroSlideResource::canCreate() ? [
                    'label' => 'Yeni slider',
                    'description' => 'Yeni vitrin görseli ekle',
                    'icon' => 'heroicon-o-plus',
                    'url' => HeroSlideResource::getUrl('create'),
                ] : null,
            ])),
            'shortcuts' => array_values(array_filter([
                ContentPageResource::canAccess() ? [
                    'label' => 'Bilgilendirme sayfaları',
                    'icon' => 'heroicon-o-document-text',
                    'url' => ContentPageResource::getUrl('index'),
                ] : null,
                BlogPostResource::canAccess() ? [
                    'label' => 'Blog yazıları',
                    'icon' => 'heroicon-o-newspaper',
                    'url' => BlogPostResource::getUrl('index'),
                ] : null,
                MediaResource::canAccess() ? [
                    'label' => 'Multimedya',
                    'icon' => 'heroicon-o-play-circle',
                    'url' => MediaResource::getUrl('index'),
                ] : null,
                SiteSettingResource::canAccess() ? [
                    'label' => 'Site ayarları',
                    'icon' => 'heroicon-o-cog-6-tooth',
                    'url' => SiteSettingResource::getUrl('index'),
                ] : null,
            ])),
            'siteSettingsUrl' => SiteSettingResource::canAccess()
                ? SiteSettingResource::getUrl('index')
                : null,
            'adminSettingsUrl' => AdminUserResource::canAccess()
                ? AdminUserResource::getUrl('index')
                : null,
        ];
    }
}
