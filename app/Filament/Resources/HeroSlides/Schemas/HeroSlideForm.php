<?php

namespace App\Filament\Resources\HeroSlides\Schemas;

use App\Filament\Support\Multilingual;
use App\Filament\Support\StorageUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HeroSlideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            // Solda slaydın içeriği (görsel + metinler), sağda yayın ayarları.
            // Dar ekranda tek kolona iner; sıra: görsel, metinler, ayarlar.
            ->columns(['default' => 1, 'xl' => 3])
            ->components([
                Group::make()
                    ->columnSpan(['default' => 1, 'xl' => 2])
                    ->schema([
                        Section::make('Görsel')
                            ->description('Vitrinde tam genişlikte gösterilir; yatay (16:9) görsel önerilir.')
                            ->schema([
                                StorageUpload::image('image_path', 'site', 'hero')
                                    ->label('Slider görseli')
                                    ->required()
                                    ->columnSpanFull(),
                                // Kayıtlı görsel, yükleme alanının JS önizlemesi
                                // gelene kadar boş beklemesin diye doğrudan basılır.
                                StorageUpload::preview('image_path', 'site')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Metinler')
                            ->description('Başlık iki satırdır; alt satır için satır sonu (Enter) kullanın. Sadece Türkçe girin; kaydettiğinizde diğer 9 dil otomatik çevrilir.')
                            ->schema([
                                Multilingual::turkish('title', 'Başlık', long: true),
                                Multilingual::turkish('button_text', 'Buton Metni'),
                            ]),
                    ]),

                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Yayın')
                            ->schema([
                                Toggle::make('active')
                                    ->label('Aktif')
                                    ->helperText('Kapalıyken slayt vitrinde görünmez.')
                                    ->default(true),
                                TextInput::make('sort_order')
                                    ->label('Sıra')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Küçük sayı önce gösterilir. Listeden sürükleyerek de sıralayabilirsiniz.'),
                            ]),

                        Section::make('Buton')
                            ->schema([
                                TextInput::make('button_url')
                                    ->label('Buton bağlantısı')
                                    ->placeholder('/#urunler')
                                    ->helperText('Site içi bağlantı (ör. /#urunler) veya tam adres.'),
                            ]),
                    ]),
            ]);
    }
}
