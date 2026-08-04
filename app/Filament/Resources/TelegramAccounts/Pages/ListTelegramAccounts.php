<?php

namespace App\Filament\Resources\TelegramAccounts\Pages;

use App\Filament\Resources\TelegramAccounts\TelegramAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTelegramAccounts extends ListRecords
{
    protected static string $resource = TelegramAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Hesap ekle')
                ->modalHeading('Yeni Telegram hesabı'),
        ];
    }
}
