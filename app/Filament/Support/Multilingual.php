<?php

namespace App\Filament\Support;

use App\Services\TranslateService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

/**
 * jsonb çok dilli alanlar (products.name, hero_slides.title ...) için
 * 10 dilli sekmeli giriş + Gemini "Çevir" aksiyonu.
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
     * Alan başına 10 sekme. tr zorunlu, diğerleri opsiyonel.
     */
    public static function tabs(string $field, string $label, bool $long = false): Tabs
    {
        return Tabs::make($label)
            ->tabs(array_map(
                fn (string $locale) => Tabs\Tab::make(self::localeLabel($locale))->schema([
                    self::input($field.'.'.$locale, $label, $long)
                        ->required($locale === 'tr')
                        ->columnSpanFull(),
                ]),
                self::locales()
            ))
            ->columnSpanFull();
    }

    private static function input(string $name, string $label, bool $long): TextInput|Textarea
    {
        return $long
            ? Textarea::make($name)->label($label)->rows(5)
            : TextInput::make($name)->label($label);
    }

    /**
     * Verilen alanların tr değerlerini alıp kalan 9 dili doldurur.
     *
     * @param  array<string, string>  $fields  ['name' => 'Ürün adı', ...]
     */
    public static function translateAction(array $fields, string $key = 'translate'): Actions
    {
        return Actions::make([
            Action::make($key)
                ->label('Çevir (9 dil)')
                ->icon('heroicon-m-language')
                ->color('gray')
                ->action(function (Get $get, Set $set) use ($fields) {
                    $source = [];

                    foreach ($fields as $field => $label) {
                        $value = trim((string) $get($field.'.tr'));

                        if ($value !== '') {
                            $source[$field] = $value;
                        }
                    }

                    if ($source === []) {
                        Notification::make()->title('Önce Türkçe alanları doldurun.')->warning()->send();

                        return;
                    }

                    try {
                        $translations = app(TranslateService::class)->translateFields($source);
                    } catch (\Throwable $exception) {
                        Notification::make()->title('Çeviri başarısız')->body($exception->getMessage())->danger()->send();

                        return;
                    }

                    foreach ($translations as $field => $values) {
                        foreach ($values as $locale => $text) {
                            $set($field.'.'.$locale, $text);
                        }
                    }

                    Notification::make()->title('Çeviriler dolduruldu.')->success()->send();
                }),
        ])->key($key.'_actions')->columnSpanFull();
    }

    /**
     * Tablo sütunlarında kullanılacak Türkçe değer.
     */
    public static function tr(mixed $value): string
    {
        return is_array($value) ? (string) ($value['tr'] ?? '') : (string) $value;
    }
}
