<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateSettings(function (array $value): array {
            foreach ((array) config('storefront.locales') as $locale) {
                $dictionaryPath = lang_path('storefront/'.$locale.'.json');
                $dictionary = is_file($dictionaryPath)
                    ? json_decode((string) file_get_contents($dictionaryPath), true)
                    : [];

                $defaults = [
                    'homeSeoTitle' => data_get($value, $locale.'.seoTitle', data_get($dictionary, 'meta.title', '')),
                    'homeSeoDescription' => data_get($value, $locale.'.seoDescription', data_get($dictionary, 'meta.description', '')),
                    'homeSeoKeywords' => data_get($value, $locale.'.seoKeywords', data_get($dictionary, 'meta.keywords', '')),
                ];

                foreach ($defaults as $key => $default) {
                    if (data_get($value, $locale.'.'.$key) === null) {
                        data_set($value, $locale.'.'.$key, (string) $default);
                    }
                }
            }

            if (data_get($value, 'general.homeSeoShareImage') === null) {
                data_set(
                    $value,
                    'general.homeSeoShareImage',
                    data_get($value, 'general.seoShareImage', data_get($value, 'general.socialShareImage')),
                );
            }

            return $value;
        });
    }

    public function down(): void
    {
        $this->updateSettings(function (array $value): array {
            foreach ((array) config('storefront.locales') as $locale) {
                data_forget($value, $locale.'.homeSeoTitle');
                data_forget($value, $locale.'.homeSeoDescription');
                data_forget($value, $locale.'.homeSeoKeywords');
            }

            data_forget($value, 'general.homeSeoShareImage');

            return $value;
        });
    }

    private function updateSettings(callable $callback): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $raw = DB::table('site_settings')->where('key', 'storefront')->value('value');
        $value = is_array($raw) ? $raw : json_decode((string) $raw, true);

        if (! is_array($value)) {
            return;
        }

        DB::table('site_settings')->where('key', 'storefront')->update([
            'value' => json_encode($callback($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);
    }
};
