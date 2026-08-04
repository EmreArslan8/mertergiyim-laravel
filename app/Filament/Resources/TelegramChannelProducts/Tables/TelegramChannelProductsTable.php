<?php

namespace App\Filament\Resources\TelegramChannelProducts\Tables;

use App\Models\TelegramChannelProduct;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TelegramChannelProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            // Sıra tek kaynaktan: en yeni paylaşım üstte. Sütun başlıklarında
            // ok yok; tıklanabilir görünüp listeyi karıştırmasın.
            ->defaultSort('posted_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('images'))
            ->emptyStateHeading('Henüz ürün çekilmedi')
            ->emptyStateDescription('Kanallardan ürün çekildikçe bu liste dolacak.')
            ->columns([
                ImageColumn::make('cover')
                    ->label('Görsel')
                    ->getStateUsing(fn (TelegramChannelProduct $record) => $record->coverUrl())
                    ->height(64),

                TextColumn::make('channel')
                    ->label('Kanal')
                    ->badge()
                    ->formatStateUsing(fn (TelegramChannelProduct $record) => $record->channelLabel()),

                TextColumn::make('name')
                    ->label('Ürün adı')
                    ->wrap()
                    // Kanalların bir kısmı isim paylaşmıyor; eksikler listede
                    // hemen görünsün diye boş bırakmak yerine işaretleniyor.
                    ->placeholder('— isim yok —')
                    ->description(fn (TelegramChannelProduct $record): ?string => match ($record->name_source) {
                        'ai' => 'görselden üretildi',
                        'manual' => 'elle girildi',
                        default => null,
                    }),

                TextColumn::make('price')
                    ->label('Fiyat')
                    ->formatStateUsing(fn ($state, TelegramChannelProduct $record): string => $state === null
                        ? '—'
                        : rtrim(rtrim(number_format((float) $state, 2, ',', '.'), '0'), ',').' '.$record->currency),

                TextColumn::make('size_series')
                    ->label('Beden')
                    ->placeholder('—'),

                TextColumn::make('colors')
                    ->label('Renkler')
                    ->formatStateUsing(fn ($state): string => filled($state) ? implode(', ', (array) $state) : '—'),

                TextColumn::make('images_count')
                    ->label('Foto')
                    ->counts('images')
                    ->alignCenter(),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => TelegramChannelProduct::STATUSES[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        'imported' => 'success',
                        'approved' => 'info',
                        'ignored' => 'danger',
                        'enriched' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('posted_at')
                    ->label('Paylaşım')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—'),
            ])
            // Arama, kanal, durum ve sıralama tek satırda dursun diye hepsi
            // filtre formuna alındı; tablonun kendi arama kutusu kapalı.
            ->searchable(false)
            // Açılıp kapanan sütun yok; "Sütunlar" menüsü boş bir kontroldü.
            ->columnManager(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            // 12 birimlik satır: arama 6, kalan üç select ikişer birim.
            ->filtersFormColumns(['default' => 1, 'sm' => 2, 'lg' => 12])
            ->deferFilters(false)
            ->filters([
                Filter::make('search')
                    ->label('Ara')
                    ->columnSpan(['lg' => 6])
                    ->schema([
                        TextInput::make('term')
                            ->label('Ara')
                            ->placeholder('Ürün adı veya kod')
                            ->prefixIcon(Heroicon::OutlinedMagnifyingGlass)
                            // Arama bu tabloda filtre satırının içinde; şeridin
                            // tam genişliği kullanması için CSS bu sınıfa bakıyor.
                            ->extraFieldWrapperAttributes(['class' => 'merter-filter-search'])
                            ->live(debounce: 500),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $term = trim((string) ($data['term'] ?? ''));

                        return $query->when($term !== '', fn (Builder $query): Builder => $query->where(
                            fn (Builder $query) => $query
                                ->where('name', 'like', "%{$term}%")
                                ->orWhere('product_code', 'like', "%{$term}%")
                        ));
                    })
                    ->indicateUsing(fn (array $data): ?string => filled($data['term'] ?? null)
                        ? 'Arama: '.$data['term']
                        : null),

                SelectFilter::make('channel')
                    ->label('Kanal')
                    ->columnSpan(['lg' => 2])
                    ->options(TelegramChannelProduct::CHANNELS)
                    ->placeholder('Tüm kanallar'),

                SelectFilter::make('status')
                    ->label('Durum')
                    ->columnSpan(['lg' => 2])
                    ->options(TelegramChannelProduct::STATUSES)
                    ->placeholder('Tüm durumlar'),

                // Sıralama da filtre satırında. Normal filtre sorgusu iç içe bir
                // where() içinde çalıştığı için orderBy oraya işlemiyor; baseQuery
                // dış sorguya yazıyor ve defaultSort'tan önce geldiği için o kazanıyor.
                SelectFilter::make('sort')
                    ->label('Sıralama')
                    ->columnSpan(['lg' => 2])
                    ->options([
                        'posted_at_desc' => 'En yeni paylaşım',
                        'posted_at_asc' => 'En eski paylaşım',
                        'price_asc' => 'Fiyat: artan',
                        'price_desc' => 'Fiyat: azalan',
                        'name_asc' => 'Ürün adı: A-Z',
                    ])
                    ->default('posted_at_desc')
                    ->selectablePlaceholder(false)
                    // Sütun adı değil sıralama anahtarı; varsayılan where kapalı.
                    ->query(fn (Builder $query): Builder => $query)
                    ->baseQuery(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'posted_at_asc' => $query->orderBy('posted_at'),
                        'price_asc' => $query->orderBy('price'),
                        'price_desc' => $query->orderByDesc('price'),
                        'name_asc' => $query->orderBy('name'),
                        default => $query->orderByDesc('posted_at'),
                    })
                    ->indicateUsing(fn (): ?string => null),
            ])
            ->recordActions([
                Action::make('open_post')
                    ->label('Telegram')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (TelegramChannelProduct $record): ?string => $record->post_url)
                    ->openUrlInNewTab()
                    ->visible(fn (TelegramChannelProduct $record): bool => filled($record->post_url)),
            ]);
    }
}
