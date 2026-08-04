<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\Multilingual;
use App\Models\Category;
use App\Models\ProductImage;
use App\Services\AdminOptionService;
use App\Support\Storefront;
use App\Support\StorefrontCache;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Field;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    /**
     * Büyük/küçük harf duyarsız arama operatörü.
     *
     * Vitrin/panel canlıda Postgres (Supabase), yerelde MariaDB çalışıyor.
     * `ilike` yalnızca Postgres'te var; MariaDB/MySQL ve SQLite'ta `like`
     * zaten harf duyarsız collation kullanıyor.
     */
    private static function likeOperator(Builder $query): string
    {
        return $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    /**
     * "Sıralama" seçimini sorguya uygular.
     *
     * Filtre closure'ı Filament tarafından iç içe bir where'e sarıldığı için
     * oradan verilen orderBy sorguya yansımıyor; bu yüzden sıralama temel
     * sorguda yapılır. Tablo seviyesinde defaultSort yok, aksi hâlde burayı ezerdi.
     */
    private static function applySort(Builder $query, $livewire): Builder
    {
        $value = $livewire->getTableFilterState('sort')['value'] ?? 'storefront';

        return match ($value) {
            'storefront' => $query->reorder('sort_order', 'asc')->orderByDesc('created_at'),
            'oldest' => $query->reorder('created_at', 'asc'),
            'price_desc' => $query->reorder('price_usd', 'desc'),
            'price_asc' => $query->reorder('price_usd', 'asc'),
            'code' => $query->reorder('code', 'asc'),
            default => $query->reorder('created_at', 'desc'),
        };
    }

    /** Filtreli bir alt kümeyi sıralamak diğer ürünlerle sıra çakıştırır. */
    private static function canReorder($livewire): bool
    {
        return ($livewire->getTableFilterState('sort')['value'] ?? 'storefront') === 'storefront'
            && blank($livewire->getTableSearch())
            && blank(data_get($livewire->getTableFilterState('category_id'), 'value'))
            && blank(data_get($livewire->getTableFilterState('active'), 'value'))
            && blank(data_get($livewire->getTableFilterState('show_on_home'), 'value'));
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            // Varsayılan sıralama tablo seviyesinde değil "Sıralama" filtresinde:
            // defaultSort her zaman uygulanıp filtrenin orderBy'ını eziyordu.
            // Uzak Supabase bağlantısında her ayrı sorgu yaklaşık 230–500 ms.
            // Liste için gereken kategori ve kapak görselini ana ürün sorgusuna
            // alt sorgu olarak ekleyerek üç ilişki sorgusunu kaldırıyoruz.
            ->modifyQueryUsing(fn (Builder $query, $livewire) => self::applySort($query, $livewire)->addSelect([
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
            ->reorderable('sort_order', fn ($livewire): bool => ProductResource::canManage() && self::canReorder($livewire))
            ->afterReordering(fn () => StorefrontCache::flushHome())
            ->reorderRecordsTriggerAction(fn (Action $action, bool $isReordering): Action => $action
                // Tek kelime: buton arama kutusunun yanında dar bir alanda duruyor.
                ->label($isReordering ? 'Bitir' : 'Sırala')
                ->button())
            // Toplam kayıt sayısı için yapılan ayrı COUNT sorgusunu kaldırır.
            ->paginationMode(PaginationMode::Simple)
            ->columns([
                ImageColumn::make('primary_image_path')
                    ->label('Görsel')
                    // Ürün fotoğrafları dikey (4:5). Kare önizlemede yanlarda
                    // beyaz boşluk kalıyordu; ölçüler merter-admin.css'te
                    // ekran boyutuna göre ayarlanıyor.
                    // Ölçü PHP'den veriliyor: Filament img'ye satır içi
                    // `height` yazıyor ve satır içi stil CSS'i eziyor.
                    ->imageWidth('3.5rem')
                    ->imageHeight('4.375rem')
                    // Sayfa başına az sayıda, çok küçük (112px webp) görsel var;
                    // lazy yerine eager + async decode ile görünen satırlar Supabase'e
                    // paralel çekilip beklemeden açılır.
                    ->extraImgAttributes(['class' => 'merter-thumb', 'loading' => 'eager', 'decoding' => 'async', 'fetchpriority' => 'high'])
                    // 56×70 px basılan önizleme için 900×1280 ham dosya
                    // indiriliyordu; transform ucundan retina için 112 px iste.
                    // Filament ImageColumn yalnızca mutlak URL'i doğrudan basar;
                    // göreli "/storage/.." adresini disk yolu sanıp bozuyordu.
                    // url() ile isteğin host'una göre mutlaklaştırıyoruz (port
                    // bağımsız kalır; Supabase mutlak URL'i değişmeden geçer).
                    ->getStateUsing(fn ($record) => $record->primary_image_path
                        ? url(Storefront::imageUrl('products', $record->primary_image_path, 112))
                        : null),
                TextColumn::make('name')
                    ->label('Ürün')
                    // jsonb dizi state'ini Filament öğe öğe basıyor; Türkçe'yi kayıttan çek.
                    ->getStateUsing(fn ($record) => Multilingual::tr($record->name))
                    ->description(fn ($record) => $record->code)
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->where('code', self::likeOperator($query), "%{$search}%")
                        ->orWhere('slug', self::likeOperator($query), "%{$search}%")
                        // Placeholder "ürün adı" vaat ediyor; slug ascii/tireli
                        // olduğu için gerçek adı (JSON name->tr) de aranmalı.
                        ->orWhere('name->tr', self::likeOperator($query), "%{$search}%")),
                TextColumn::make('category_name')->label('Kategori')->placeholder('-'),
                TextColumn::make('price_usd')
                    ->label('Fiyat (USD)')
                    ->formatStateUsing(fn ($state) => Storefront::formatPrice($state, ['symbol' => '$', 'position' => 'prefix'])),
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
                ToggleColumn::make('active')
                    ->label('Yayında')
                    ->disabled(fn (): bool => ! ProductResource::canManage()),
                // Ana sayfa vitrinini listeden yönetebilmek için: ürünü tek tek
                // açıp kapatmadan buradan işaretleniyor.
                ToggleColumn::make('show_on_home')
                    ->label('Ana sayfada')
                    ->disabled(fn (): bool => ! ProductResource::canManage()),
            ])
            // Filtreler açılır menüde saklıydı; panel kullanıcısı hangi filtrenin
            // olduğunu görmüyordu. Artık tablonun üstünde, tam genişlikte.
            // Açılıp kapanan sütun yok; "Sütunlar" menüsü boş bir kontroldü.
            ->columnManager(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            // Mobilde "Yayın durumu" gizli; kalan üç select satırı tam paylaşsın.
            ->filtersFormColumns(['default' => 3, 'lg' => 4])
            // Seçim anında uygulansın: "Filtreleri uygula" butonu hem fazladan
            // tıklama hem de üst şeritte satır kaydırması demekti.
            ->deferFilters(false)
            ->searchPlaceholder('Ürün adı, kodu veya bağlantı adresinde ara')
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->options(fn () => app(AdminOptionService::class)->categories())
                    ->placeholder('Tüm kategoriler'),
                TernaryFilter::make('active')
                    ->label('Yayın durumu')
                    ->placeholder('Hepsi')
                    ->trueLabel('Yayında')
                    ->falseLabel('Yayında değil')
                    // Dar ekranda filtre şeridi kalabalık; bu select mobilde
                    // gizleniyor (merter-admin.css `.merter-hide-on-mobile`).
                    ->modifyFormFieldUsing(fn (Field $field): Field => $field
                        ->extraFieldWrapperAttributes(['class' => 'merter-hide-on-mobile'])),
                TernaryFilter::make('show_on_home')
                    ->label('Ana sayfa')
                    ->placeholder('Hepsi')
                    ->trueLabel('Ana sayfada')
                    ->falseLabel('Ana sayfada değil'),
                // Tek sıralama kaynağı burası. Sütun başlıklarındaki oklar
                // kaldırıldı: sıralama temel sorguda uygulandığı için başlığa
                // tıklamak hiçbir şey değiştirmiyor, ölü bir kontrol oluyordu.
                SelectFilter::make('sort')
                    ->label('Sıralama')
                    ->default('storefront')
                    ->selectablePlaceholder(false)
                    // Sıralamanın her zaman bir değeri var; "Aktif filtreler"
                    // şeridinde rozet olarak görünmesi gürültü yaratıyordu.
                    ->indicateUsing(fn (): array => [])
                    ->options([
                        'storefront' => 'Varsayılan',
                        'newest' => 'En yeni eklenen',
                        'oldest' => 'En eski eklenen',
                        'price_desc' => 'Fiyat: yüksekten düşüğe',
                        'price_asc' => 'Fiyat: düşükten yükseğe',
                        'code' => 'Ürün kodu (A-Z)',
                    ])
                    // Filtre yalnızca seçim arayüzü; sıralama modifyQueryUsing'de
                    // uygulanır (filtre closure'ı iç içe where'e sarıldığı için
                    // oradaki orderBy sorguya yansımıyor).
                    ->query(fn (Builder $query): Builder => $query),
            ])
            ->recordActions([
                // Global EditAction modal açıyor. Ürün formu büyük olduğu için
                // düz bağlantı aksiyonu doğrudan tam sayfa düzenlemeye gider.
                Action::make('edit')
                    ->label('Düzenle')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->button()
                    ->visible(fn ($record): bool => ProductResource::canEdit($record))
                    ->url(fn ($record): string => ProductResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make()
                    ->visible(fn ($record): bool => ProductResource::canDelete($record))
                    ->modalDescription('Bu ürünü silerseniz adı yeniden kullanılabilir hâle gelir ve aynı ürün ikinci kez girilebilir. Kalıcı kayıtlarda silmek yerine "Yayında" seçeneğini kapatmanız önerilir.'),
            ]);
    }
}
