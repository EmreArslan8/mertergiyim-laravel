<?php

namespace App\Filament\Resources\Sizes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SizeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Beden')->required()->unique(ignoreRecord: true),
                TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Toggle::make('active')->label('Aktif')->default(true),
            ]);
    }
}
