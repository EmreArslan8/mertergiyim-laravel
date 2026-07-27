<?php

namespace App\Filament\Resources\SiteSettings\Schemas;

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
                TextInput::make('key')
                    ->label('Anahtar')
                    ->required()
                    ->disabledOn('edit')
                    ->default('storefront'),
                Section::make('Alt bilgi metinleri')
                    ->description('Sadece Türkçe girin; kaydettiğinizde diğer 9 dil otomatik çevrilir.')
                    ->schema([
                        TextInput::make('value.tr.footerBrand')->label(self::FIELDS['footerBrand'].' (Türkçe)'),
                        Textarea::make('value.tr.footerDescription')->label(self::FIELDS['footerDescription'].' (Türkçe)')->rows(3),
                        Textarea::make('value.tr.footerAddress')->label(self::FIELDS['footerAddress'].' (Türkçe)')->rows(2),
                        TextInput::make('value.tr.footerInfoTitle')->label(self::FIELDS['footerInfoTitle'].' (Türkçe)'),
                        TextInput::make('value.tr.copyright')->label(self::FIELDS['copyright'].' (Türkçe)'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
