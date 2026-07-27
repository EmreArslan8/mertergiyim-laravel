<?php

namespace App\Filament\Resources\HeroSlides\Schemas;

use App\Filament\Support\Multilingual;
use App\Filament\Support\StorageUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HeroSlideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Görsel')
                    ->schema([
                        StorageUpload::image('image_path', 'site')
                            ->label('Slider görseli')
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make('Metinler')
                    ->description('Başlık iki satırdır; alt satır için satır sonu (Enter) kullanın.')
                    ->schema([
                        Multilingual::translateAction([
                            'title' => 'Başlık',
                            'button_text' => 'Buton metni',
                        ]),
                        Multilingual::tabs('title', 'Başlık', long: true),
                        Multilingual::tabs('button_text', 'Buton metni'),
                    ]),
                Section::make('Ayarlar')
                    ->columns(3)
                    ->schema([
                        TextInput::make('button_url')->label('Buton bağlantısı')->helperText('Örn: /#urunler'),
                        TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                        Toggle::make('active')->label('Aktif')->default(true),
                    ]),
            ]);
    }
}
