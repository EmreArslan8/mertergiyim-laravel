<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Support\Multilingual;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name_i18n')
                    ->label('Kategori')
                    ->getStateUsing(fn ($record) => Multilingual::tr($record->name_i18n))
                    ->searchable(query: fn ($query, string $search) => $query->where('name', 'like', "%{$search}%")),
                TextColumn::make('slug')->label('Slug')->searchable()->color('gray'),
                IconColumn::make('active')->label('Aktif')->boolean(),
                TextColumn::make('products_count')->label('Ürün')->counts('products'),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(fn (array $data, $livewire, ?Model $record): array => $livewire->fillAutomaticTranslationsFor($data, $record)),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
