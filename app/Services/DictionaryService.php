<?php

namespace App\Services;

use Illuminate\Support\Arr;

/**
 * lib/i18n/dictionaries.ts karşılığı: resources/lang/storefront/{locale}.json
 * dosyalarını olduğu gibi okur, anahtar yapısı (meta.title, home.buy ...) korunur.
 */
class DictionaryService
{
    /** @var array<string, array<string, mixed>> */
    private array $loaded = [];

    public function all(string $locale): array
    {
        if (isset($this->loaded[$locale])) {
            return $this->loaded[$locale];
        }

        $path = lang_path('storefront/'.$locale.'.json');

        if (! is_file($path)) {
            $path = lang_path('storefront/'.config('storefront.default_locale').'.json');
        }

        return $this->loaded[$locale] = json_decode((string) file_get_contents($path), true) ?: [];
    }

    public function get(string $locale, string $key, string $default = ''): mixed
    {
        return Arr::get($this->all($locale), $key, $default);
    }
}
