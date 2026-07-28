<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Support\Multilingual;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Multilingual::turkish('name_i18n', 'Kategori adı')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('URL\'de kullanılır, benzersiz olmalı.'),
                Toggle::make('active')->label('Aktif')->default(true),
            ]);
    }
}
