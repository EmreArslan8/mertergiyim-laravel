<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Support\Multilingual;
use App\Models\Category;
use App\Support\Storefront;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            // N+1 önlemi: görseller ve kategori tek sorguda gelsin (uzak DB'de her sorgu ~250 ms).
            ->modifyQueryUsing(fn ($query) => $query->with(['images', 'category']))
            ->columns([
                ImageColumn::make('primary_image')
                    ->label('Görsel')
                    ->getStateUsing(function ($record) {
                        $image = Storefront::sortedImages($record->images)[0] ?? null;

                        return $image ? Storefront::storageUrl('products', $image->storage_path) : null;
                    }),
                TextColumn::make('name')
                    ->label('Ürün')
                    // jsonb dizi state'ini Filament öğe öğe basıyor; Türkçe'yi kayıttan çek.
                    ->getStateUsing(fn ($record) => Multilingual::tr($record->name))
                    ->description(fn ($record) => $record->code)
                    ->searchable(query: fn ($query, string $search) => $query
                        ->where('code', 'ilike', "%{$search}%")
                        ->orWhere('slug', 'ilike', "%{$search}%")),
                TextColumn::make('category.name')->label('Kategori')->placeholder('-'),
                TextColumn::make('price')
                    ->label('Fiyat')
                    ->formatStateUsing(fn ($state, $record) => Storefront::formatPrice($state, ['symbol' => $record->currency, 'position' => 'suffix'])),
                TextColumn::make('stock_status')
                    ->label('Stok')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'out_of_stock' => 'Tükendi',
                        'low_stock' => 'Son ürünler',
                        default => 'Stokta',
                    })
                    ->color(fn ($state) => match ($state) {
                        'out_of_stock' => 'danger',
                        'low_stock' => 'warning',
                        default => 'success',
                    }),
                IconColumn::make('active')->label('Yayında')->boolean(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id')),
                TernaryFilter::make('active')->label('Yayın durumu'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
