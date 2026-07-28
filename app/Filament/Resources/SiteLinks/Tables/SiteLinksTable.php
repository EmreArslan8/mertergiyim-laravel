<?php

namespace App\Filament\Resources\SiteLinks\Tables;

use App\Filament\Support\Multilingual;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SiteLinksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('location')
                    ->label('Konum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'footer' ? 'Alt menü' : 'Üst menü'),
                TextColumn::make('label')
                    ->label('Etiket')
                    ->getStateUsing(fn ($record) => Multilingual::tr($record->label)),
                TextColumn::make('url')->label('URL')->color('gray'),
                TextColumn::make('sort_order')->label('Sıra'),
                ToggleColumn::make('active')->label('Aktif / Pasif'),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(fn (array $data, $livewire, ?Model $record): array => $livewire->fillAutomaticTranslationsFor($data, $record)),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
