<?php

namespace App\Filament\Resources\Homepage\Pages;

use App\Filament\Resources\HeroSlides\HeroSlideResource;
use App\Filament\Resources\Homepage\HomepageResource;
use App\Filament\Resources\Homepage\Schemas\HomepageForm;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\SiteSettings\Pages\EditSiteSetting;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class EditHomepage extends EditSiteSetting
{
    protected static string $resource = HomepageResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getTitle(): string
    {
        return 'Ana Sayfa Yönetimi';
    }

    public function getSubheading(): string
    {
        return 'Hero · Ürün Bölümü · Kategoriler · Boş Durumlar · SEO';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('heroSlides')
                ->label('Hero / Slider')
                ->icon(Heroicon::OutlinedPhoto)
                ->color('gray')
                ->url(fn (): string => HeroSlideResource::getUrl('index')),
            Action::make('products')
                ->label('Ürünler')
                ->icon(Heroicon::OutlinedShoppingBag)
                ->color('gray')
                ->url(fn (): string => ProductResource::getUrl('index')),
        ];
    }

    protected function translatableJsonFields(): array
    {
        return HomepageForm::FIELDS;
    }
}
