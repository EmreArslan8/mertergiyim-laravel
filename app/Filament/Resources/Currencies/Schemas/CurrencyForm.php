<?php

namespace App\Filament\Resources\Currencies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Toggle::make('is_default')
                    ->label('Varsayılan para birimi')
                    ->helperText('Yalnızca bir para birimi varsayılan olabilir.'),
            ]);
    }
}
