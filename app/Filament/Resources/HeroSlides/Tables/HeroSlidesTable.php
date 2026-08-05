<?php

namespace App\Filament\Resources\HeroSlides\Tables;

use App\Filament\Resources\HeroSlides\HeroSlideResource;
use App\Filament\Support\Multilingual;
use App\Filament\Support\Reorderable;
use App\Support\Storefront;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class HeroSlidesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            // Satırın tamamı slaytı açar; düzenleme modalda değil kendi
            // sayfasında yapılıyor (görsel yükleme ve iki dilli metinler
            // modal içinde sıkışıyordu).
            ->recordUrl(fn ($record): string => HeroSlideResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->reorderRecordsTriggerAction(Reorderable::triggerAction())
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Önizleme')
                    // Çerçeve/boşluk olmadan, görselin tamamı görünecek şekilde.
                    ->imageWidth('7rem')
                    ->imageHeight('3.9375rem')
                    ->extraImgAttributes(['class' => 'merter-thumb-wide', 'loading' => 'lazy'])
                    // Filament ImageColumn yalnızca mutlak URL'i doğrudan basar;
                    // göreli "/storage/.." adresini disk yolu sanıp bozuyordu.
                    // url() ile isteğin host'una göre mutlaklaştırıyoruz (Supabase
                    // mutlak URL'i değişmeden geçer).
                    ->getStateUsing(fn ($record) => $record->image_path
                        ? url(Storefront::storageUrl('site', $record->image_path))
                        : null),
                TextColumn::make('title')
                    ->label('Başlık')
                    // title jsonb array (10 dil); getStateUsing tek HtmlString
                    // döndürdüğü için Filament listeye çevirip 10 kez basmaz.
                    // richText yalnızca tr'yi okuyup izinli etiketleri bırakır;
                    // TextColumn state'i e() ile kaçırır ama HtmlString'i olduğu
                    // gibi basar.
                    ->getStateUsing(fn ($record) => new HtmlString(Storefront::richText($record->title, 'tr')))
                    ->wrap(),
                TextColumn::make('button_text')
                    ->label('Buton')
                    ->getStateUsing(fn ($record) => Multilingual::tr($record->button_text))
                    ->description(fn ($record) => $record->button_url),
                TextColumn::make('sort_order')->label('Sıra'),
                ToggleColumn::make('active')->label('Aktif / Pasif'),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn ($record): string => HeroSlideResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ]);
    }
}
