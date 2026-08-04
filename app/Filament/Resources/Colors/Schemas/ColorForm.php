<?php

namespace App\Filament\Resources\Colors\Schemas;

use App\Filament\Support\Multilingual;
use App\Models\Color;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ColorForm
{
    private const PRESETS = [
        '#000000' => 'Siyah',
        '#FFFFFF' => 'Beyaz',
        '#9CA3AF' => 'Gri',
        '#991B1B' => 'Bordo',
        '#EF2B2D' => 'Kırmızı',
        '#F25C12' => 'Turuncu',
        '#F59E0B' => 'Kehribar',
        '#EAB308' => 'Sarı',
        '#16A34A' => 'Yeşil',
        '#0D9488' => 'Turkuaz',
        '#2563EB' => 'Mavi',
        '#1E3A8A' => 'Lacivert',
        '#7C3AED' => 'Mor',
        '#DB2777' => 'Pembe',
        '#78350F' => 'Kahverengi',
        '#F5F1DC' => 'Krem',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            // Modal zaten kart görünümü veriyor; sarmalayan Section içeride
            // ikinci bir çerçeve oluşturuyordu. Alanlar doğrudan kök şemada.
            ->columns(1)
            ->components([
                Multilingual::turkish('name_i18n', 'Renk adı', legacyFallback: 'name')
                    ->unique(table: Color::class, column: 'name', ignoreRecord: true)
                    ->columnSpanFull(),
                ColorPicker::make('hex')
                    ->label('Renk')
                    ->hex()
                    ->default('#000000')
                    ->regex('/^#[0-9A-Fa-f]{6}$/')
                    ->validationMessages(['regex' => 'Renk kodunu #000000 biçiminde girin.'])
                    ->dehydrateStateUsing(fn (?string $state): string => strtoupper((string) $state))
                    ->live(debounce: 250)
                    ->required()
                    ->helperText('Kutuya tıklayıp seçin, aşağıdan hazır renk kullanın ya da HEX kodunu yazın.')
                    ->extraAttributes(['class' => 'merter-color-picker'])
                    ->extraInputAttributes([
                        'class' => 'merter-color-picker__hex',
                        'spellcheck' => 'false',
                    ])
                    ->columnSpanFull(),
                Actions::make(self::presetActions())
                    ->label('Hazır renkler')
                    ->extraAttributes(['class' => 'merter-color-palette'])
                    ->columnSpanFull(),
            ]);
    }

    /** @return array<Action> */
    private static function presetActions(): array
    {
        return array_map(
            fn (string $hex, string $label): Action => Action::make('preset'.ltrim($hex, '#'))
                ->label($label)
                // Yazı düğmenin üstünde görünmesin: renk adı zaten üzerine
                // gelince ipucu olarak çıkıyor. Etiket DOM'dan kalkıyor,
                // erişilebilirlik aria-label ile sürüyor.
                ->hiddenLabel()
                ->tooltip($label.' · '.$hex)
                ->extraAttributes(fn (Get $get): array => [
                    'class' => 'merter-color-preset'.(
                        strtoupper((string) $get('hex')) === $hex ? ' is-selected' : ''
                    ),
                    'style' => '--merter-preset: '.$hex,
                    'aria-label' => $label.' '.$hex,
                ])
                ->action(function (Set $set, Get $get) use ($hex, $label): void {
                    $set('hex', $hex);

                    // Renk adı boşsa ya da daha önce bir hazır renk adıyla
                    // otomatik dolduysa güncelle; kullanıcının elle yazdığı
                    // özel isim ezilmesin.
                    $current = trim((string) $get('name_i18n.tr'));
                    if ($current === '' || in_array($current, self::PRESETS, true)) {
                        $set('name_i18n.tr', $label);
                    }
                }),
            array_keys(self::PRESETS),
            array_values(self::PRESETS),
        );
    }
}
