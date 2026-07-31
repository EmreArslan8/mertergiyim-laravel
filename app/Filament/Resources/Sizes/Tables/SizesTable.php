<?php

namespace App\Filament\Resources\Sizes\Tables;

use App\Filament\Support\Multilingual;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SizesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name_i18n')
                    ->label('Beden')
                    ->getStateUsing(fn ($record) => Multilingual::tr($record->name_i18n) ?: $record->name)
                    ->searchable(query: fn ($query, string $search) => $query->where('name', 'like', "%{$search}%")),
                TextColumn::make('sort_order')->label('Sıra'),
                ToggleColumn::make('active')->label('Aktif / Pasif'),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(fn (array $data, $livewire, ?Model $record): array => $livewire->fillAutomaticTranslationsFor($data, $record)),
                DeleteAction::make(),
            ]);
    }
}
