<?php

namespace App\Filament\Resources\Languages\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Dil kodu')
                    ->required()
                    ->maxLength(10)
                    ->helperText('ISO dil kodu; ör. tr, en, ar.')
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Görünen ad')
                    ->required()
                    ->helperText('Dil çubuğunda görünen etiket.'),
                // Sıra otomatik: yeni dil sona eklenir, tabloda sürükle-bırakla
                // düzenlenir. Elle input gerekmiyor.
                Toggle::make('active')->label('Aktif')->default(true),
            ]);
    }
}
