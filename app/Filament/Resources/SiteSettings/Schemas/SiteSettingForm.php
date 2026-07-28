<?php

namespace App\Filament\Resources\SiteSettings\Schemas;

use App\Filament\Support\StorageUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiteSettingForm
{
    /**
     * site_settings.value jsonb yapısı: { locale: { alan: değer } }.
     */
    public const FIELDS = [
        'siteName' => 'Site adı',
        'footerBrand' => 'Alt bilgi marka adı',
        'footerDescription' => 'Alt bilgi açıklaması',
        'footerAddress' => 'Adres',
        'footerInfoTitle' => 'Bilgi başlığı',
        'copyright' => 'Telif metni',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Marka ve iletişim')
                    ->description('Site genelinde kullanılan marka, logo ve iletişim bilgileri.')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextInput::make('value.tr.siteName')
                            ->label(self::FIELDS['siteName'].' (Türkçe)')
                            ->required()
                            ->helperText('Diğer 9 dil kaydederken otomatik hazırlanır.'),
                        StorageUpload::image('value.general.siteLogo', 'site', 'branding')
                            ->label('Site logosu')
                            ->imageEditor()
                            ->helperText('Boş bırakılırsa site adı metin olarak gösterilir.'),
                        TextInput::make('value.general.whatsappNumber')
                            ->label('WhatsApp numarası')
                            ->tel()
                            ->regex('/^[0-9]{10,15}$/')
                            ->placeholder('905323259788')
                            ->helperText('Ülke koduyla, sadece rakam kullanın.'),
                        TextInput::make('value.general.contactPhone')
                            ->label('İletişim telefonu')
                            ->tel()
                            ->placeholder('+90 532 325 97 88'),
                        TextInput::make('value.general.contactEmail')
                            ->label('E-posta')
                            ->email()
                            ->placeholder('info@mertergiyim.com'),
                        TextInput::make('value.general.instagramUrl')
                            ->label('Instagram bağlantısı')
                            ->url()
                            ->placeholder('https://instagram.com/mertergiyim'),
                    ])
                    ->columnSpanFull(),
                Section::make('Alt bilgi (footer)')
                    ->description('Sadece Türkçe girin; kaydettiğinizde diğer 9 dil otomatik çevrilir.')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextInput::make('value.tr.footerBrand')->label(self::FIELDS['footerBrand'].' (Türkçe)'),
                        TextInput::make('value.tr.footerInfoTitle')->label(self::FIELDS['footerInfoTitle'].' (Türkçe)'),
                        Textarea::make('value.tr.footerDescription')
                            ->label(self::FIELDS['footerDescription'].' (Türkçe)')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('value.tr.footerAddress')
                            ->label(self::FIELDS['footerAddress'].' (Türkçe)')
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('value.tr.copyright')->label(self::FIELDS['copyright'].' (Türkçe)'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
