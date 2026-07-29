<?php

namespace App\Filament\Resources\HeroSlides\Pages;

use App\Filament\Resources\HeroSlides\HeroSlideResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListHeroSlides extends ListRecords
{
    protected static string $resource = HeroSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Yeni slider')
                ->icon(Heroicon::Plus)
                ->url(fn (): string => HeroSlideResource::getUrl('create')),
        ];
    }
}
