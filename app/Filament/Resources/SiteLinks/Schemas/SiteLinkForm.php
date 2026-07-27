<?php

namespace App\Filament\Resources\SiteLinks\Schemas;

use App\Filament\Support\Multilingual;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiteLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bağlantı')
                    ->columns(2)
                    ->schema([
                        Select::make('location')
                            ->label('Konum')
                            ->options(['header' => 'Üst menü', 'footer' => 'Alt menü'])
                            ->default('header')
                            ->required(),
                        TextInput::make('link_key')
                            ->label('Anahtar')
                            ->required()
                            ->helperText('Konum içinde benzersiz. Örn: home, products, cart'),
                        TextInput::make('url')
                            ->label('URL')
                            ->required()
                            ->helperText('Site içi yollar dil ön ekiyle otomatik tamamlanır. Örn: /#urunler'),
                        TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                        Toggle::make('active')->label('Aktif')->default(true),
                    ]),
                Section::make('Etiket')
                    ->description('Sadece Türkçe girin; kaydettiğinizde diğer 9 dil otomatik çevrilir.')
                    ->schema([
                        Multilingual::turkish('label', 'Etiket'),
                    ]),
            ]);
    }
}
