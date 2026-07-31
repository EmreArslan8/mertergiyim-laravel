<?php

namespace App\Filament\Resources\HeroSlides\Tables;

use App\Filament\Resources\HeroSlides\HeroSlideResource;
use App\Filament\Support\Multilingual;
use App\Support\Storefront;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

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
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Önizleme')
                    // Çerçeve/boşluk olmadan, görselin tamamı görünecek şekilde.
                    ->imageWidth('7rem')
                    ->imageHeight('3.9375rem')
                    ->extraImgAttributes(['class' => 'merter-thumb-wide', 'loading' => 'lazy'])
                    ->getStateUsing(fn ($record) => Storefront::storageUrl('site', $record->image_path)),
                TextColumn::make('title')
                    ->label('Başlık')
                    ->getStateUsing(fn ($record) => str_replace("\n", ' / ', Multilingual::tr($record->title))),
                TextColumn::make('button_text')
                    ->label('Buton')
                    ->getStateUsing(fn ($record) => Multilingual::tr($record->button_text))
                    ->description(fn ($record) => $record->button_url),
                TextColumn::make('sort_order')->label('Sıra'),
                ToggleColumn::make('active')->label('Aktif / Pasif'),
            ])
            ->recordActions([
                EditAction::make()
                    // Global EditAction ayarı modal açıyor; burada tam sayfa.
                    ->modal(false)
                    ->url(fn ($record): string => HeroSlideResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ]);
    }
}
