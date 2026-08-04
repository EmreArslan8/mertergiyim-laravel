<?php

namespace App\Filament\Resources\TelegramAccounts\Tables;

use App\Models\TelegramAccount;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TelegramAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->emptyStateHeading('Hesap tanımlı değil')
            ->emptyStateDescription('Hesap eklenmeden çekim hesapsız yoldan yapılır; görseller önizleme çözünürlüğünde gelir.')
            ->columns([
                TextColumn::make('label')
                    ->label('Hesap')
                    ->formatStateUsing(fn (TelegramAccount $record): string => $record->label())
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('Numara')
                    // Tam numara listede durmasın; kayda girince görünüyor.
                    ->formatStateUsing(fn (TelegramAccount $record): string => $record->maskedPhone())
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Giriş')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => TelegramAccount::STATUSES[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'awaiting_code' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->description(fn (TelegramAccount $record): ?string => $record->status === 'failed'
                        ? Str::limit($record->last_error, 60)
                        : null),

                TextColumn::make('last_used_at')
                    ->label('Son kullanım')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                ToggleColumn::make('active')->label('Aktif / Pasif'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Hesabı düzenle'),
                DeleteAction::make(),
            ]);
    }
}
