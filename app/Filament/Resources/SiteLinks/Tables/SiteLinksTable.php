<?php

namespace App\Filament\Resources\SiteLinks\Tables;

use App\Filament\Support\Multilingual;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SiteLinksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('location')
                    ->label('Konum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'footer' ? 'Alt menü' : 'Üst menü'),
                TextColumn::make('label')
                    ->label('Etiket')
                    ->formatStateUsing(fn ($state) => Multilingual::tr($state)),
                TextColumn::make('url')->label('URL')->color('gray'),
                TextColumn::make('sort_order')->label('Sıra'),
                IconColumn::make('active')->label('Aktif')->boolean(),
            ])
            ->filters([])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
