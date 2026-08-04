<?php

namespace App\Filament\Resources\Currencies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kod')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(8)
                    ->helperText('Örn: TRY, USD, EUR')
                    ->afterStateUpdated(fn ($state, $set) => $set('code', strtoupper((string) $state))),
                TextInput::make('symbol')->label('Sembol')->required()->maxLength(8),
                Select::make('position')
                    ->label('Sembol konumu')
                    ->options(['prefix' => 'Önde ($100)', 'suffix' => 'Arkada (100 TL)'])
                    ->default('suffix')
                    ->required(),
                // Sıra otomatik: yeni para birimi sona eklenir, tabloda
                // sürükle-bırakla düzenlenir. Elle input gerekmiyor.
                //
                // "Varsayılan" kaldırıldı: fiyat motoru USD tabanlı ve para
                // birimini dile göre seçtiği için is_default hiç kullanılmıyordu.
            ]);
    }
}
