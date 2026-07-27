<?php

namespace App\Filament\Resources\Colors\Schemas;

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
                TextInput::make('name')->label('Renk adı')->required()->unique(ignoreRecord: true),
                ColorPicker::make('hex')->label('Renk')->hex()->default('#ffffff')->required(),
                TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Toggle::make('active')->label('Aktif')->default(true),
            ]);
    }
}
