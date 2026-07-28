<?php

namespace App\Filament\Resources\Colors\Tables;

use App\Filament\Support\Multilingual;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ColorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ColorColumn::make('hex')->label('Renk'),
                TextColumn::make('name_i18n')
                    ->label('Renk adı')
                    ->getStateUsing(fn ($record) => Multilingual::tr($record->name_i18n))
                    ->searchable(query: fn ($query, string $search) => $query->where('name', 'like', "%{$search}%")),
                TextColumn::make('sort_order')->label('Sıra'),
                IconColumn::make('active')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(fn (array $data, $livewire, ?Model $record): array => $livewire->fillAutomaticTranslationsFor($data, $record)),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
