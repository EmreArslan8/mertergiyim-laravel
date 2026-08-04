<?php

namespace App\Filament\Resources\Currencies\Tables;

use App\Filament\Support\Reorderable;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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
            ->reorderRecordsTriggerAction(Reorderable::triggerAction())
            ->columns([
                TextColumn::make('code')->label('Kod')->searchable()->weight('bold'),
                TextColumn::make('symbol')->label('Sembol'),
                TextColumn::make('position')
                    ->label('Konum')
                    ->formatStateUsing(fn ($state) => $state === 'prefix' ? 'Önde' : 'Arkada'),
                TextColumn::make('sort_order')->label('Sıra'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
