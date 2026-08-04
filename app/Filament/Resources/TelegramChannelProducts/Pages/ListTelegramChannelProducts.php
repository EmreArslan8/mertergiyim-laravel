<?php

namespace App\Filament\Resources\TelegramChannelProducts\Pages;

use App\Filament\Pages\TelegramScans;
use App\Filament\Resources\TelegramAccounts\TelegramAccountResource;
use App\Filament\Resources\TelegramChannelProducts\TelegramChannelProductResource;
use App\Filament\Resources\TelegramChannels\TelegramChannelResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListTelegramChannelProducts extends ListRecords
{
    protected static string $resource = TelegramChannelProductResource::class;

    /**
     * Kayıtlar panelden değil scraper'dan gelir; ekleme aksiyonu yok.
     *
     * Modülün diğer ekranları kenar çubuğunu kalabalıklaştırmasın diye
     * menüden çıkarıldı; buradan açılıyorlar.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('scan')
                ->label('Ürün çek')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->url(fn (): string => TelegramScans::getUrl()),

            Action::make('channels')
                ->label('Kanallar')
                ->icon(Heroicon::OutlinedHashtag)
                ->color('gray')
                ->url(fn (): string => TelegramChannelResource::getUrl()),

            Action::make('accounts')
                ->label('Hesaplar')
                ->icon(Heroicon::OutlinedKey)
                ->color('gray')
                ->url(fn (): string => TelegramAccountResource::getUrl()),
        ];
    }
}
