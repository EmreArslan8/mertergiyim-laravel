<?php

namespace App\Filament\Resources\HeroSlides\Tables;

use App\Filament\Resources\HeroSlides\HeroSlideResource;
use App\Filament\Support\Multilingual;
use App\Support\Storefront;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Önizleme')
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
                    ->url(fn ($record): string => HeroSlideResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
