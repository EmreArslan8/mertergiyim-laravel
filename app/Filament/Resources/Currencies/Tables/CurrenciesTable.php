<?php

namespace App\Filament\Resources\Currencies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CurrenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('code')->label('Kod')->searchable()->weight('bold'),
                TextColumn::make('symbol')->label('Sembol'),
                TextColumn::make('position')
                    ->label('Konum')
                    ->formatStateUsing(fn ($state) => $state === 'prefix' ? 'Önde' : 'Arkada'),
                IconColumn::make('is_default')->label('Varsayılan')->boolean(),
                TextColumn::make('sort_order')->label('Sıra'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
