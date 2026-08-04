<?php

namespace App\Filament\Resources\Languages\Tables;

use App\Filament\Support\Reorderable;
use App\Support\StorefrontCache;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class LanguagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->reorderRecordsTriggerAction(Reorderable::triggerAction())
            ->afterReordering(fn () => StorefrontCache::flushChrome())
            ->columns([
                TextColumn::make('code')->label('Kod')->weight('bold'),
                TextColumn::make('name')->label('Dil')->searchable(),
                TextColumn::make('sort_order')->label('Sıra'),
                ToggleColumn::make('active')->label('Aktif / Pasif'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
