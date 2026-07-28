<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Support\Multilingual;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Multilingual::turkish('name_i18n', 'Kategori adı', legacyFallback: 'name')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $set, $get): void {
                        if (blank($get('slug'))) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                Hidden::make('slug'),
                Toggle::make('active')->label('Aktif')->default(true),
            ]);
    }
}
