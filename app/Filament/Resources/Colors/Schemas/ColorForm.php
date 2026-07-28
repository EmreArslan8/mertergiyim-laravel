<?php

namespace App\Filament\Resources\Colors\Schemas;

use App\Filament\Support\Multilingual;
use App\Models\Color;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ColorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Multilingual::turkish('name_i18n', 'Renk adı', legacyFallback: 'name')
                    ->unique(table: Color::class, column: 'name', ignoreRecord: true),
                ColorPicker::make('hex')->label('Renk')->hex()->default('#ffffff')->required(),
                TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Toggle::make('active')
                    ->label('Durum')
                    ->default(true)
                    ->live()
                    ->onColor('success')
                    ->offColor('danger')
                    ->helperText(fn (?bool $state): string => $state ? 'Aktif' : 'Pasif'),
            ]);
    }
}
