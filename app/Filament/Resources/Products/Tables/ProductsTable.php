<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Support\Multilingual;
use App\Models\Category;
use App\Models\ProductImage;
use App\Services\AdminOptionService;
use App\Support\Storefront;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->defaultSort('created_at', 'desc')
            // Uzak Supabase bağlantısında her ayrı sorgu yaklaşık 230–500 ms.
            // Liste için gereken kategori ve kapak görselini ana ürün sorgusuna
            // alt sorgu olarak ekleyerek üç ilişki sorgusunu kaldırıyoruz.
            ->modifyQueryUsing(fn (Builder $query) => $query->addSelect([
                'category_name' => Category::query()
                    ->select('name')
                    ->whereColumn('categories.id', 'products.category_id')
                    ->limit(1),
                'primary_image_path' => ProductImage::query()
                    ->select('storage_path')
                    ->whereColumn('product_images.product_id', 'products.id')
                    ->orderByDesc('is_primary')
                    ->orderBy('sort_order')
                    ->limit(1),
            ]))
            // Toplam kayıt sayısı için yapılan ayrı COUNT sorgusunu kaldırır.
            ->paginationMode(PaginationMode::Simple)
            ->columns([
                ImageColumn::make('primary_image_path')
                    ->label('Görsel')
                    ->getStateUsing(fn ($record) => $record->primary_image_path
                        ? Storefront::storageUrl('products', $record->primary_image_path)
                        : null),
                TextColumn::make('name')
                    ->label('Ürün')
                    // jsonb dizi state'ini Filament öğe öğe basıyor; Türkçe'yi kayıttan çek.
                    ->getStateUsing(fn ($record) => Multilingual::tr($record->name))
                    ->description(fn ($record) => $record->code)
                    ->searchable(query: fn ($query, string $search) => $query
                        ->where('code', 'ilike', "%{$search}%")
                        ->orWhere('slug', 'ilike', "%{$search}%")),
                TextColumn::make('category_name')->label('Kategori')->placeholder('-'),
                TextColumn::make('price_try')
                    ->label('Fiyat (TRY)')
                    ->formatStateUsing(fn ($state) => Storefront::formatPrice($state, ['symbol' => 'TL', 'position' => 'suffix'])),
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
                ToggleColumn::make('active')->label('Yayında'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->options(fn () => app(AdminOptionService::class)->categories()),
                TernaryFilter::make('active')->label('Yayın durumu'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Ürün düzenle')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->extraModalWindowAttributes(['class' => 'merter-product-modal'])
                    ->mutateDataUsing(fn (array $data, $livewire, ?Model $record): array => $livewire->fillAutomaticTranslationsFor($data, $record)),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
