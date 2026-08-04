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
            ->columns(1)
            ->components([
                // Yayın durumu formun en başında, tek ve görünür bir anahtar.
                // Sıralama ayrı bir alan değil; listeden sürükleyerek yapılır.
                Toggle::make('active')
                    ->label('Aktif')
                    ->helperText('Kapalıyken slayt vitrinde görünmez.')
                    ->default(true),

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
                    ->description('Başlıkta kalın/italik gibi biçimlendirme ve satır sonu kullanabilirsiniz. Sadece Türkçe girin; kaydettiğinizde diğer 9 dil otomatik çevrilir.')
                    ->schema([
                        Multilingual::turkish('eyebrow', 'Üst yazı', required: false)
                            ->placeholder('mertergiyim.com')
                            ->helperText('Başlığın üstünde küçük yazıyla gösterilir. Boş bırakılırsa basılmaz.'),
                        Multilingual::turkish('title', 'Başlık', richInline: true),
                        Multilingual::turkish('button_text', 'Buton Metni'),
                        TextInput::make('button_url')
                            ->label('Buton bağlantısı')
                            ->placeholder('/#urunler')
                            ->helperText('Site içi bağlantı (ör. /#urunler) veya tam adres.'),
                    ]),
            ]);
    }
}
