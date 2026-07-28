<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

/**
 * jsonb çok dilli alanlar (products.name, hero_slides.title ...) için panel
 * yardımcıları.
 *
 * Panelde yalnızca Türkçe girilir; kalan 9 dil kayıt anında otomatik çevrilir
 * (App\Filament\Concerns\TranslatesJsonFields).
 */
class Multilingual
{
    /**
     * @return array<int, string>
     */
    public static function locales(): array
    {
        return config('storefront.locales');
    }

    public static function localeLabel(string $locale): string
    {
        return [
            'tr' => 'Türkçe',
            'en' => 'İngilizce',
            'ar' => 'Arapça',
            'ru' => 'Rusça',
            'fa' => 'Farsça',
            'uk' => 'Ukraynaca',
            'fr' => 'Fransızca',
            'de' => 'Almanca',
            'es' => 'İspanyolca',
            'it' => 'İtalyanca',
        ][$locale] ?? strtoupper($locale);
    }

    /**
     * Panelde tek Türkçe alan.
     */
    public static function turkish(
        string $field,
        string $label,
        bool $long = false,
        bool $required = true,
        ?string $legacyFallback = null,
    ): TextInput|Textarea
    {
        $input = self::input($field.'.tr', $label, $long)
            ->required($required)
            ->columnSpanFull();

        if ($legacyFallback) {
            $input->afterStateHydrated(function ($component, $state, $record) use ($legacyFallback): void {
                if (blank($state) && filled($record?->{$legacyFallback})) {
                    $component->state($record->{$legacyFallback});
                }
            });
        }

        return $input;
    }

    private static function input(string $name, string $label, bool $long): TextInput|Textarea
    {
        return $long
            ? Textarea::make($name)->label($label)->rows(5)
            : TextInput::make($name)->label($label);
    }

    /**
     * Tablo sütunlarında kullanılacak Türkçe değer.
     */
    public static function tr(mixed $value): string
    {
        return is_array($value) ? (string) ($value['tr'] ?? '') : (string) $value;
    }
}
