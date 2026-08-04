<?php

namespace App\Filament\Resources\Colors\Tables;

use App\Filament\Support\Multilingual;
use App\Filament\Support\Reorderable;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ColorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            // Sürükle-bırak ikonu tek başına ne işe yaradığını anlatmıyordu;
            // ürün listesindeki gibi yazılı bir düğmeye çevrildi.
            ->reorderRecordsTriggerAction(Reorderable::triggerAction())
            ->columns([
                ColorColumn::make('color_preview')
                    ->label('Renk')
                    ->getStateUsing(fn ($record) => $record->hex),
                TextColumn::make('hex')
                    ->label('Renk kodu')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('name_i18n')
                    ->label('Renk adı')
                    ->getStateUsing(fn ($record) => Multilingual::tr($record->name_i18n) ?: $record->name)
                    ->searchable(query: fn ($query, string $search) => $query->where('name', 'like', "%{$search}%")),
                TextColumn::make('sort_order')->label('Sıra'),
                ToggleColumn::make('active')->label('Aktif / Pasif'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Rengi düzenle')
                    ->modalWidth(Width::TwoExtraLarge)
                    ->extraModalWindowAttributes(['class' => 'merter-color-modal'])
                    ->mutateDataUsing(fn (array $data, $livewire, ?Model $record): array => $livewire->fillAutomaticTranslationsFor($data, $record)),
                DeleteAction::make(),
            ]);
    }
}
