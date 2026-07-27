<?php

namespace App\Filament\Resources\SiteSettings\Schemas;

use App\Filament\Support\Multilingual;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class SiteSettingForm
{
    /**
     * site_settings.value jsonb yapısı: { locale: { alan: değer } }.
     */
    private const FIELDS = [
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
                Tabs::make('Diller')
                    ->tabs(array_map(
                        fn (string $locale) => Tabs\Tab::make(Multilingual::localeLabel($locale))->schema([
                            TextInput::make('value.'.$locale.'.footerBrand')->label(self::FIELDS['footerBrand']),
                            Textarea::make('value.'.$locale.'.footerDescription')->label(self::FIELDS['footerDescription'])->rows(3),
                            Textarea::make('value.'.$locale.'.footerAddress')->label(self::FIELDS['footerAddress'])->rows(2),
                            TextInput::make('value.'.$locale.'.footerInfoTitle')->label(self::FIELDS['footerInfoTitle']),
                            TextInput::make('value.'.$locale.'.copyright')->label(self::FIELDS['copyright']),
                        ]),
                        Multilingual::locales()
                    ))
                    ->columnSpanFull(),
            ]);
    }
}
