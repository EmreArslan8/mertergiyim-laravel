<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Support\Multilingual;
use App\Filament\Support\Reorderable;
use App\Models\Category;
use App\Support\StorefrontCache;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            // Sıra vitrindeki kategori sekmelerini belirler; sürükle-bırak
            // sort_order'a yazar, bu yüzden liste de sort_order'a göre dizilir.
            ->defaultSort('sort_order')
            // Arama aktifken alt küme görünür; sürüklemek global sırayı bozar.
            ->reorderable('sort_order', fn ($livewire): bool => CategoryResource::canManage()
                && blank($livewire->getTableSearch()))
            ->reorderRecordsTriggerAction(Reorderable::triggerAction())
            ->afterReordering(fn () => StorefrontCache::flushFor(new Category))
            ->columns([
                TextColumn::make('name_i18n')
                    ->label('Kategori')
                    ->getStateUsing(fn ($record) => Multilingual::tr($record->name_i18n) ?: $record->name)
                    ->searchable(query: fn ($query, string $search) => $query->where('name', 'like', "%{$search}%")),
                TextColumn::make('slug')->label('Slug')->searchable()->color('gray'),
                ToggleColumn::make('active')->label('Aktif / Pasif'),
                TextColumn::make('products_count')->label('Ürün')->counts('products'),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(fn (array $data, $livewire, ?Model $record): array => $livewire->fillAutomaticTranslationsFor($data, $record)),
                DeleteAction::make(),
            ]);
    }
}
