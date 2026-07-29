<?php

namespace App\Filament\Resources\SiteSettings\Tables;

use App\Filament\Resources\SiteSettings\SiteSettingResource;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SiteSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->columns([
                TextColumn::make('key')->label('Anahtar')->weight('bold'),
                TextColumn::make('value')
                    ->label('Doldurulmuş diller')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', array_keys($state)) : '-'),
                TextColumn::make('updated_at')->label('Güncellendi')->dateTime('d.m.Y H:i'),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn ($record): string => SiteSettingResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
