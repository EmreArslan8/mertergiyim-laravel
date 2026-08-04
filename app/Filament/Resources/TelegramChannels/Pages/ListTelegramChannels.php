<?php

namespace App\Filament\Resources\TelegramChannels\Pages;

use App\Filament\Resources\TelegramChannels\TelegramChannelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListTelegramChannels extends ListRecords
{
    protected static string $resource = TelegramChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Kanal ekle')
                ->modalHeading('Yeni kanal ekle')
                ->modalWidth(Width::Large),
        ];
    }
}
