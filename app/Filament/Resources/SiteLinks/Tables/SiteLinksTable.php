<?php

namespace App\Filament\Resources\SiteLinks\Tables;

use App\Filament\Support\Multilingual;
use App\Support\StorefrontCache;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SiteLinksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->defaultSort('sort_order')
            // Üst ve alt menü ListSiteLinks üzerindeki yatay sekmelerde ayrılır.
            ->reorderable('sort_order')
            // Sıralama düğmesi sekmelerin bulunduğu üst satırda gösterilir.
            ->reorderRecordsTriggerAction(fn (Action $action): Action => $action->hidden())
            ->afterReordering(fn () => StorefrontCache::flushChrome())
            ->columns([
                TextColumn::make('label')
                    ->label('Etiket')
                    ->getStateUsing(fn ($record) => Multilingual::tr($record->label)),
                TextColumn::make('url')->label('URL')->color('gray'),
                TextColumn::make('sort_order')->label('Sıra'),
                ToggleColumn::make('active')->label('Aktif / Pasif'),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(fn (array $data, $livewire, ?Model $record): array => $livewire->fillAutomaticTranslationsFor($data, $record)),
                DeleteAction::make(),
            ]);
    }
}
