<?php

namespace App\Filament\Resources\TelegramChannels\Tables;

use App\Models\TelegramChannel;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TelegramChannelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->emptyStateHeading('Kanal eklenmemiş')
            ->emptyStateDescription('Ürün çekilecek Telegram kanallarını buraya ekleyin.')
            ->columns([
                TextColumn::make('title')
                    ->label('Kanal')
                    ->formatStateUsing(fn (TelegramChannel $record): string => $record->label())
                    ->description(fn (TelegramChannel $record): string => '@'.$record->username)
                    ->searchable(),

                TextColumn::make('last_scanned_at')
                    ->label('Son tarama')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('hiç taranmadı'),

                ToggleColumn::make('active')->label('Aktif / Pasif'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Telegram')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (TelegramChannel $record): string => $record->url())
                    ->openUrlInNewTab(),

                EditAction::make()
                    ->modalHeading('Kanalı düzenle')
                    ->modalWidth(Width::Large),

                DeleteAction::make()
                    // Çekilmiş ürünler kanal kaydına bağlı değil (kanal adı
                    // metin olarak saklanıyor); silmek geçmişi bozmaz.
                    ->modalDescription('Kanal listeden çıkar. Daha önce çekilen ürünler kalır.'),
            ]);
    }
}
