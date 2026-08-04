<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FIELDS = [
        'homeCategoryTitle' => 'categories',
        'homeAllCategoriesLabel' => 'allCategories',
        'homeCollectionLabel' => 'collection',
        'homeFeaturedTitle' => 'featuredProducts',
        'homeOrderNotice' => 'orderNotice',
        'homeEmptyTitle' => 'empty',
        'homeEmptyDescription' => 'emptyDescription',
        'homeFilterEmptyTitle' => 'filterEmpty',
        'homeFilterEmptyDescription' => 'filterEmptyDescription',
        'homeShowAllProductsLabel' => 'showAllProducts',
    ];

    public function up(): void
    {
        $this->updateSettings(function (array $value): array {
            foreach ((array) config('storefront.locales') as $locale) {
                $path = lang_path('storefront/'.$locale.'.json');
                $dictionary = is_file($path)
                    ? json_decode((string) file_get_contents($path), true)
                    : [];

                foreach (self::FIELDS as $settingKey => $dictionaryKey) {
                    if (data_get($value, $locale.'.'.$settingKey) === null) {
                        data_set(
                            $value,
                            $locale.'.'.$settingKey,
                            (string) data_get($dictionary, 'home.'.$dictionaryKey, ''),
                        );
                    }
                }
            }

            return $value;
        });
    }

    public function down(): void
    {
        $this->updateSettings(function (array $value): array {
            foreach ((array) config('storefront.locales') as $locale) {
                foreach (array_keys(self::FIELDS) as $settingKey) {
                    data_forget($value, $locale.'.'.$settingKey);
                }
            }

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
