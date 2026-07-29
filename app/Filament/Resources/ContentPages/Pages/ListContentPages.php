<?php

namespace App\Filament\Resources\ContentPages\Pages;

use App\Filament\Resources\ContentPages\ContentPageResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListContentPages extends ListRecords
{
    protected static string $resource = ContentPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Yeni sayfa')
                ->icon(Heroicon::Plus)
                ->url(fn (): string => ContentPageResource::getUrl('create')),
        ];
    }
}
