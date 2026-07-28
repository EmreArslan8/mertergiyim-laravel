<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $categories = [
            'kadin-giyim' => [
                'tr' => 'Kadın Giyim',
                'en' => "Women's Clothing",
                'ar' => 'ملابس نسائية',
                'ru' => 'Женская одежда',
                'fa' => 'پوشاک زنانه',
                'uk' => 'Жіночий одяг',
                'fr' => 'Vêtements pour femmes',
                'de' => 'Damenbekleidung',
                'es' => 'Ropa de mujer',
                'it' => 'Abbigliamento donna',
            ],
            'elbiseler' => [
                'tr' => 'Elbiseler',
                'en' => 'Dresses',
                'ar' => 'فساتين',
                'ru' => 'Платья',
                'fa' => 'پیراهن‌ها',
                'uk' => 'Сукні',
                'fr' => 'Robes',
                'de' => 'Kleider',
                'es' => 'Vestidos',
                'it' => 'Abiti',
            ],
            'takimlar' => [
                'tr' => 'Takımlar',
                'en' => 'Sets',
                'ar' => 'أطقم',
                'ru' => 'Комплекты',
                'fa' => 'ست‌ها',
                'uk' => 'Комплекти',
                'fr' => 'Ensembles',
                'de' => 'Sets',
                'es' => 'Conjuntos',
                'it' => 'Completi',
            ],
            'keten' => [
                'tr' => 'Keten',
                'en' => 'Linen',
                'ar' => 'كتان',
                'ru' => 'Лён',
                'fa' => 'کتان',
                'uk' => 'Льон',
                'fr' => 'Lin',
                'de' => 'Leinen',
                'es' => 'Lino',
                'it' => 'Lino',
            ],
        ];

        foreach ($categories as $slug => $translations) {
            $row = DB::table('categories')->where('slug', $slug)->first(['name_i18n']);

            if (! $row) {
                continue;
            }

            $existing = is_string($row->name_i18n)
                ? json_decode($row->name_i18n, true)
                : (array) $row->name_i18n;
            $existing = is_array($existing)
                ? array_filter($existing, fn ($value) => trim((string) $value) !== '')
                : [];

            DB::table('categories')->where('slug', $slug)->update([
                'name_i18n' => json_encode(
                    array_merge($translations, $existing),
                    JSON_UNESCAPED_UNICODE,
                ),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // İçerik çevirileri geri alınırken silinmez.
    }
};
