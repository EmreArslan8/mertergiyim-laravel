<?php

namespace App\Filament\Resources\Languages\Schemas;

use App\Filament\Support\Multilingual;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('code')
                    ->label('Dil kodu')
                    ->options(collect(Multilingual::locales())
                        ->mapWithKeys(fn ($locale) => [$locale => $locale.' — '.Multilingual::localeLabel($locale)])
                        ->all())
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Görünen ad')
                    ->required()
                    ->helperText('Dil çubuğunda görünen etiket.'),
                TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Toggle::make('active')->label('Aktif')->default(true),
            ]);
    }
}
