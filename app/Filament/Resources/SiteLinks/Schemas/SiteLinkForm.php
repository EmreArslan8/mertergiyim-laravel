<?php

namespace App\Filament\Resources\SiteLinks\Schemas;

use App\Filament\Support\Multilingual;
use App\Support\Storefront;
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
                            ->placeholder('/hakkimizda')
                            ->helperText('Dil ön eki yazmayın. /hakkimizda girin; ziyaretçinin diline göre /tr/hakkimizda, /en/hakkimizda olarak açılır. Dış bağlantılar https:// ile.')
                            // Kullanıcı yine de /tr/... yazarsa veriyi ön eksiz kaydet.
                            ->dehydrateStateUsing(fn (?string $state): ?string => self::stripLocalePrefix($state)),
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

    /**
     * Site içi yollarda dil ön ekini ayıklar: "/tr/hakkimizda" → "/hakkimizda".
     * Dış bağlantılar (https://, mailto:, tel:, #) olduğu gibi kalır.
     */
    private static function stripLocalePrefix(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || preg_match('#^(https?://|mailto:|tel:|\#)#i', $url)) {
            return $url === '' ? null : $url;
        }

        if (! str_starts_with($url, '/')) {
            $url = '/'.$url;
        }

        $segments = explode('/', $url);

        if (Storefront::hasLocale($segments[1] ?? '')) {
            array_splice($segments, 1, 1);
        }

        return implode('/', $segments) ?: '/';
    }
}
