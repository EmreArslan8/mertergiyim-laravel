<?php

namespace App\Filament\Support;

use Filament\Forms\Components\RichEditor;
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
        bool $rich = false,
    ): TextInput|Textarea|RichEditor
    {
        $input = self::input($field.'.tr', $label, $long, $rich)
            ->required($required)
            ->columnSpanFull();

        // Not: afterStateHydrated tek closure tutar; zengin editörün eski düz metin
        // dönüşümü de burada yapılır ki iki kanca birbirini ezmesin.
        if ($legacyFallback || $rich) {
            $input->afterStateHydrated(function ($component, $state, $record) use ($legacyFallback, $rich): void {
                if ($legacyFallback && blank($state) && filled($record?->{$legacyFallback})) {
                    $state = $record->{$legacyFallback};
                }

                $component->state($rich ? self::toEditorHtml($state) : $state);
            });
        }

        return $input;
    }

    private static function input(string $name, string $label, bool $long, bool $rich = false): TextInput|Textarea|RichEditor
    {
        if ($rich) {
            return self::richEditor($name, $label);
        }

        return $long
            ? Textarea::make($name)->label($label)->rows(5)
            : TextInput::make($name)->label($label);
    }

    /**
     * Panel kullanıcısı için sadeleştirilmiş editör: kalın/italik, madde
     * işaretli/numaralı liste ve geri/ileri. Başlık, tablo, kod, renk, hizalama
     * ve dosya ekleme kapalı; böylece vitrin tasarımını bozacak içerik üretilemez
     * ve otomatik çeviri basit HTML üzerinde güvenle çalışır.
     */
    public static function richEditor(string $name, string $label): RichEditor
    {
        return RichEditor::make($name)
            ->label($label)
            ->toolbarButtons([
                ['bold', 'italic'],
                ['bulletList', 'orderedList'],
                ['undo', 'redo'],
            ])
            ->fileAttachments(false)
            ->helperText('Metni kalın/italik yapabilir, madde listesi ekleyebilirsiniz. Biçim vitrinde aynı görünür.');
    }

    /**
     * Editör öncesi kaydedilmiş düz metinleri paragraflara çevirir; zaten HTML
     * olan içeriğe dokunmaz.
     */
    private static function toEditorHtml(mixed $state): string
    {
        $value = trim((string) $state);

        if ($value === '' || preg_match('/<[a-z][a-z0-9]*\b[^>]*>/i', $value)) {
            return $value;
        }

        $paragraphs = preg_split('/\R{2,}/u', $value) ?: [$value];

        return collect($paragraphs)
            ->map(fn (string $paragraph) => trim($paragraph))
            ->filter()
            ->map(fn (string $paragraph) => '<p>'.nl2br(e($paragraph), false).'</p>')
            ->implode('');
    }

    /**
     * Tablo sütunlarında kullanılacak Türkçe değer.
     */
    public static function tr(mixed $value): string
    {
        return is_array($value) ? (string) ($value['tr'] ?? '') : (string) $value;
    }
}
