<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Bir jsonb çok dilli alanın 9 yabancı dilde eksiksiz doldurulup
 * doldurulmadığını raporlar.
 *
 * Otomatik çeviri Gemini'ye bağlı olduğu için tek tek isteklerin başarısız
 * olması mümkün; bu sınıf hem panelde durum rozetini hem de
 * "translations:check" komutunu besler.
 */
class TranslationStatus
{
    /**
     * Çevrilmesi beklenen diller (tr kaynak dildir, listeye girmez).
     *
     * @return array<int, string>
     */
    public static function expectedLocales(): array
    {
        return config('storefront.translation.languages');
    }

    /**
     * Tek bir jsonb değerinde eksik olan diller.
     *
     * Türkçe boşsa alan hiç doldurulmamış demektir; eksik dil raporlanmaz.
     *
     * @return array<int, string>
     */
    public static function missingLocales(mixed $value): array
    {
        $value = self::toArray($value);

        if (trim((string) ($value['tr'] ?? '')) === '') {
            return [];
        }

        return array_values(array_filter(
            self::expectedLocales(),
            fn (string $locale) => trim((string) ($value[$locale] ?? '')) === '',
        ));
    }

    /**
     * Bir kaydın verilen alanlarındaki eksikleri alan bazında döner.
     *
     * @param  array<int, string>  $fields
     * @return array<string, array<int, string>> ['name' => ['ar', 'fa'], ...]
     */
    public static function missingForRecord(Model $record, array $fields): array
    {
        $missing = [];

        foreach ($fields as $field) {
            $locales = self::missingLocales($record->getAttribute($field));

            if ($locales !== []) {
                $missing[$field] = $locales;
            }
        }

        return $missing;
    }

    /**
     * @param  array<int, string>  $fields
     */
    public static function isComplete(Model $record, array $fields): bool
    {
        return self::missingForRecord($record, $fields) === [];
    }

    /**
     * "name: Arapça, Farsça" biçiminde okunur özet.
     *
     * @param  array<string, array<int, string>>  $missing
     */
    public static function summary(array $missing): string
    {
        $parts = [];

        foreach ($missing as $field => $locales) {
            $parts[] = $field.': '.implode(', ', $locales);
        }

        return implode(' | ', $parts);
    }

    private static function toArray(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? $value : [];
    }
}
