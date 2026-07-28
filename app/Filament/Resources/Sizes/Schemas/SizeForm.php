<?php

namespace App\Filament\Resources\Sizes\Schemas;

use App\Filament\Support\Multilingual;
use App\Models\Size;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SizeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Multilingual::turkish('name_i18n', 'Beden', legacyFallback: 'name')
                    ->unique(table: Size::class, column: 'name', ignoreRecord: true),
                TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Toggle::make('active')->label('Aktif')->default(true),
            ]);
    }
}
