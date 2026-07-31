<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Resources\Orders\OrderResource;
use App\Support\OrderStatus;
use App\Support\Storefront;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    /**
     * "Sıralama" seçimini sorguya uygular.
     *
     * Filtre closure'ı Filament tarafından iç içe bir where'e sarıldığı için
     * oradan verilen orderBy sorguya yansımıyor; sıralama temel sorguda yapılır.
     * Bu yüzden tablo seviyesinde defaultSort yok, olsaydı burayı ezerdi.
     */
    private static function applySort(Builder $query, $livewire): Builder
    {
        $value = $livewire->getTableFilterState('sort')['value'] ?? 'newest';

        return match ($value) {
            'oldest' => $query->reorder('created_at', 'asc'),
            'total_desc' => $query->reorder('total', 'desc'),
            'total_asc' => $query->reorder('total', 'asc'),
            'order_number' => $query->reorder('order_number', 'asc'),
            default => $query->reorder('created_at', 'desc'),
        };
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            // Satırın tamamı siparişi açar. Öncesinde bazı hücreler (kopyala
            // özelliği olan takip kodu, rozetler) tıklamayı yutuyordu; hangi
            // sütuna denk geldiğine göre bazen açılıp bazen açılmıyordu.
            ->recordUrl(fn ($record): string => OrderResource::getUrl('edit', ['record' => $record]))
            // Sütun düzeni referans panelle aynı:
            // # · Sipariş · Takip · Durum · Müşteri · Kargo · Tarih · İşlem
            // Kalem sayısı için ek sorgu atılmasın diye withCount kullanılır.
            ->modifyQueryUsing(fn ($query, $livewire) => self::applySort($query->withCount('items'), $livewire))
            ->columns([
                // Mobilde satır kart olarak diziliyor; sayfa içi sıra numarası
                // orada bir şeye referans vermiyor, yalnızca satır uzatıyor.
                TextColumn::make('index')
                    ->label('#')
                    ->visibleFrom('md')
                    ->rowIndex(),
                TextColumn::make('order_number')
                    ->label('Sipariş')
                    ->weight('bold')
                    ->description(fn ($record): string => ($record->items_count ?? 0).' kalem')
                    ->searchable(),
                TextColumn::make('tracking_code')
                    ->label('Takip')
                    ->placeholder('—')
                    // Diğer metinlerden ayrılsın diye renkli, ama kırmızı değil:
                    // panelde kırmızı hata/dikkat demek, takip kodu nötr veri.
                    ->color('info')
                    // Mobil kartta sekiz etiket-değer çifti okunmuyor; takip
                    // ve kargo detay sayfasında zaten var.
                    ->visibleFrom('md')
                    // Kopyalama sipariş detayında; listede tıklama satırın
                    // kendisine ait olmalı.
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => OrderStatus::label($state))
                    ->color(fn ($state) => OrderStatus::color($state)),
                TextColumn::make('customer_name')
                    ->label('Müşteri')
                    ->description(fn ($record) => $record->phone)
                    // Telefon bu sütunun altında görünüyor ama aranabilir
                    // değildi. Ayrıca numaralar "0535 123 45 67" gibi ayraçlı
                    // kaydediliyor: aramadaki ve kayıttaki rakamlar ayrıca
                    // sadeleştirilmezse "5351234567" hiçbir zaman eşleşmez.
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $digits = preg_replace('/\D+/', '', $search) ?? '';

                        return $query->where(function (Builder $query) use ($search, $digits): void {
                            $query->where('customer_name', 'like', '%'.$search.'%');

                            if ($digits !== '') {
                                $query->orWhereRaw(
                                    "replace(replace(replace(replace(phone, ' ', ''), '-', ''), '(', ''), ')', '') like ?",
                                    ['%'.$digits.'%'],
                                );
                            }
                        });
                    }),
                // Rozet değil düz metin: "Kargoda / Bekliyor" rozeti kargo
                // firmasının dolu olmasına bakıyordu, siparişin gerçek
                // durumuna değil. Durumu "Yeni" olan siparişe firma girilince
                // satırda hem "Yeni" hem "Kargoda" yazıyordu. Durum bilgisi
                // yandaki sütunda; burada yalnızca firma adı var.
                TextColumn::make('cargo_company')
                    ->label('Kargo')
                    // Boş çizgi yerine ne anlama geldiğini yazan kısa metin.
                    ->placeholder('Verilmedi')
                    ->visibleFrom('md')
                    // Takip kodu kendi sütununda aranıyor, burada tekrarlanmaz.
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->date('d.m.Y')
                    ->description(fn ($record): string => $record->created_at?->format('H:i') ?? ''),
                TextColumn::make('total')
                    ->label('Toplam')
                    ->alignEnd()
                    // Eşit genişlikte rakam: tutarların ondalıkları alt alta.
                    ->extraCellAttributes(['class' => 'merter-tabular'])
                    ->formatStateUsing(fn ($state, $record) => Storefront::formatPrice($state, match ($record->currency ?? 'TRY') {
                        'USD' => ['symbol' => '$', 'position' => 'prefix'],
                        'EUR' => ['symbol' => '€', 'position' => 'suffix'],
                        default => ['symbol' => 'TL', 'position' => 'suffix'],
                    })),
            ])
            // Ürün listesindeki gibi: filtreler açılır menüde saklanmıyor,
            // üst şeritte select olarak duruyor ve seçim anında uygulanıyor.
            // Açılıp kapanan sütun yok; "Sütunlar" menüsü boş bir kontroldü.
            ->columnManager(false)
            // Toplam kayıt sayısı için ayrı COUNT sorgusu atılmaz; uzak
            // veritabanında her ek sorgu sayfa açılışını uzatıyor.
            ->paginationMode(PaginationMode::Simple)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->deferFilters(false)
            ->searchPlaceholder('Sipariş no, müşteri veya takip kodunda ara')
            ->filters([
                SelectFilter::make('status')->label('Durum')->options(OrderStatus::labels()),
                // Ürün listesindekiyle aynı mantık: tek sıralama kaynağı bu
                // select. Filtre closure'ı iç içe where'e sarıldığı için
                // orderBy burada değil, modifyQueryUsing içinde uygulanır.
                SelectFilter::make('sort')
                    ->label('Sıralama')
                    ->default('newest')
                    ->selectablePlaceholder(false)
                    ->indicateUsing(fn (): array => [])
                    ->options([
                        'newest' => 'En yeni sipariş',
                        'oldest' => 'En eski sipariş',
                        'total_desc' => 'Tutar: yüksekten düşüğe',
                        'total_asc' => 'Tutar: düşükten yükseğe',
                        'order_number' => 'Sipariş no (A-Z)',
                    ])
                    ->query(fn (Builder $query): Builder => $query),
            ])
            ->emptyStateHeading('Henüz sipariş yok')
            ->emptyStateDescription('Vitrinden gelen siparişler burada listelenir; elle de sipariş açabilirsiniz.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Yeni sipariş')
                    ->icon(Heroicon::Plus)
                    ->url(fn (): string => OrderResource::getUrl('create')),
            ])
            // Satıra tıklamak da siparişi açıyor; buton görünür ve alışılmış
            // olan yol olduğu için duruyor.
            ->recordActions([
                EditAction::make()
                    ->label('Düzenle')
                    ->button()
                    ->outlined()
                    // Global EditAction ayarı modal açıyor; sipariş düzenleme
                    // tam sayfa olduğu için modal kapatılır.
                    ->modal(false)
                    ->url(fn ($record): string => OrderResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make()
                    ->button()
                    ->outlined(),
            ]);
    }
}
