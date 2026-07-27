<?php

namespace App\Filament\Resources\HeroSlides\Tables;

use App\Filament\Support\Multilingual;
use App\Support\Storefront;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HeroSlidesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Önizleme')
                    ->getStateUsing(fn ($record) => Storefront::storageUrl('site', $record->image_path)),
                TextColumn::make('title')
                    ->label('Başlık')
                    ->formatStateUsing(fn ($state) => str_replace("\n", ' / ', Multilingual::tr($state))),
                TextColumn::make('button_text')
                    ->label('Buton')
                    ->formatStateUsing(fn ($state) => Multilingual::tr($state))
                    ->description(fn ($record) => $record->button_url),
                TextColumn::make('sort_order')->label('Sıra'),
                IconColumn::make('active')->label('Aktif')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
