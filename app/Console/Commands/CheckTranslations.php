<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Color;
use App\Models\ContentPage;
use App\Models\HeroSlide;
use App\Models\MediaFile;
use App\Models\MediaPost;
use App\Models\Product;
use App\Models\SiteLink;
use App\Models\SiteSetting;
use App\Models\Size;
use App\Services\TranslateService;
use App\Support\Storefront;
use App\Support\TranslationStatus;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * Otomatik çeviri Gemini'ye bağlı olduğu için tek tek istekler başarısız
 * olabiliyor ve kayıt yine de Türkçe ile kaydediliyor. Bu komut hangi kayıtların
 * hangi dillerde eksik olduğunu raporlar, --fix ile eksikleri tamamlar.
 *
 * Canlıya almadan önce çıktının boş olması beklenir.
 */
class CheckTranslations extends Command
{
    private const MAX_REPAIR_ATTEMPTS = 3;

    protected $signature = 'translations:check
        {--fix : Eksik dilleri Gemini ile tamamla}
        {--model= : Yalnızca tek bir kaynağı tara (ör. Product)}';

    protected $description = 'Çok dilli alanlarda eksik kalan çevirileri raporlar ve isteğe bağlı tamamlar.';

    /**
     * Taranacak model => çevrilebilir alanlar.
     *
     * @var array<class-string<Model>, array<int, string>>
     */
    private const SOURCES = [
        Product::class => ['name', 'description'],
        ContentPage::class => ['title', 'content', 'seo_title', 'seo_description'],
        BlogPost::class => ['title', 'excerpt', 'content'],
        HeroSlide::class => ['title', 'button_text'],
        Category::class => ['name_i18n'],
        Color::class => ['name_i18n'],
        Size::class => ['name_i18n'],
        MediaPost::class => ['title', 'description'],
        MediaFile::class => ['alt'],
        SiteLink::class => ['label'],
    ];

    /**
     * SiteSetting diğer modellerden farklı olarak çevirileri
     * value.{locale}.{field} biçiminde saklar.
     */
    private const SITE_SETTING_FIELDS = [
        'siteName',
        'footerBrand',
        'footerDescription',
        'footerAddress',
        'footerInfoTitle',
        'copyright',
        'contactTitle',
        'contactDescription',
        'contactAddress',
        'seoTitle',
        'seoDescription',
        'seoKeywords',
        'whatsappMessage',
        'maintenanceTitle',
        'maintenanceMessage',
        'orderSuccessText',
        'homeCategoryTitle',
        'homeAllCategoriesLabel',
        'homeCollectionLabel',
        'homeFeaturedTitle',
        'homeOrderNotice',
        'homeEmptyTitle',
        'homeEmptyDescription',
        'homeFilterEmptyTitle',
        'homeFilterEmptyDescription',
        'homeShowAllProductsLabel',
        'homeSeoTitle',
        'homeSeoDescription',
        'homeSeoKeywords',
    ];

    public function handle(TranslateService $translator): int
    {
        $fix = (bool) $this->option('fix');
        $only = (string) $this->option('model');
        $dictionaryIssues = $only === '' ? $this->checkDictionaries() : 0;

        if ($fix && ! $translator->configured()) {
            $this->error('GEMINI_API_KEY tanımlı değil; --fix kullanılamaz.');

            return self::FAILURE;
        }

        $incomplete = 0;
        $repaired = 0;
        $failed = 0;

        foreach (self::SOURCES as $model => $fields) {
            if ($only !== '' && class_basename($model) !== $only) {
                continue;
            }

            foreach ($model::query()->cursor() as $record) {
                $missing = TranslationStatus::missingForRecord($record, $fields);

                if ($missing === []) {
                    continue;
                }

                $incomplete++;
                $label = class_basename($model).' #'.$record->getKey();
                $this->line($label.' → '.TranslationStatus::summary($missing));

                if (! $fix) {
                    continue;
                }

                try {
                    $this->repair($translator, $record, array_keys($missing));
                    $repaired++;
                    $this->info('  tamamlandı');
                } catch (Throwable $exception) {
                    $failed++;
                    $this->error('  hata: '.$exception->getMessage());
                }
            }
        }

        if ($only === '' || $only === 'SiteSetting') {
            [$siteIncomplete, $siteRepaired, $siteFailed] = $this->checkSiteSetting($translator, $fix);
            $incomplete += $siteIncomplete;
            $repaired += $siteRepaired;
            $failed += $siteFailed;
        }

        if ($incomplete === 0 && $dictionaryIssues === 0) {
            $this->info('Tüm çok dilli alanlar 9 yabancı dilde eksiksiz.');

            return self::SUCCESS;
        }

        if ($incomplete > 0) {
            $this->newLine();
            $this->warn($incomplete.' kayıtta eksik çeviri var.');
        }

        if ($dictionaryIssues > 0) {
            $this->warn($dictionaryIssues.' sözlük sorunu var; JSON dosyaları elle düzeltilmeli.');
        }

        if ($fix) {
            $this->line('Tamamlanan: '.$repaired.', başarısız: '.$failed);
        }

        // Eksik varken 1 döner; CI/deploy adımı buna bakarak durabilir.
        return $dictionaryIssues > 0 || $failed > 0 || (! $fix && $incomplete > 0)
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * Statik vitrin sözlüklerinin anahtar, boş değer ve placeholder bütünlüğünü
     * Türkçe kaynak sözlüğe göre denetler.
     */
    private function checkDictionaries(): int
    {
        $sourcePath = lang_path('storefront/tr.json');

        try {
            $source = Arr::dot(json_decode(
                (string) file_get_contents($sourcePath),
                true,
                flags: JSON_THROW_ON_ERROR,
            ));
        } catch (JsonException $exception) {
            $this->error('tr.json geçersiz JSON: '.$exception->getMessage());

            return 1;
        }

        $issues = 0;

        foreach (Storefront::locales() as $locale) {
            $path = lang_path('storefront/'.$locale.'.json');

            if (! is_file($path)) {
                $this->error($locale.'.json bulunamadı.');
                $issues++;

                continue;
            }

            try {
                $dictionary = Arr::dot(json_decode(
                    (string) file_get_contents($path),
                    true,
                    flags: JSON_THROW_ON_ERROR,
                ));
            } catch (JsonException $exception) {
                $this->error($locale.'.json geçersiz JSON: '.$exception->getMessage());
                $issues++;

                continue;
            }

            foreach (array_diff_key($source, $dictionary) as $key => $_value) {
                $this->line($locale.'.json → eksik anahtar: '.$key);
                $issues++;
            }

            foreach (array_diff_key($dictionary, $source) as $key => $_value) {
                $this->line($locale.'.json → fazla anahtar: '.$key);
                $issues++;
            }

            foreach (array_intersect_key($dictionary, $source) as $key => $value) {
                if (trim((string) $value) === '') {
                    $this->line($locale.'.json → boş değer: '.$key);
                    $issues++;
                }

                if ($this->placeholders((string) $value) !== $this->placeholders((string) $source[$key])) {
                    $this->line($locale.'.json → placeholder uyuşmazlığı: '.$key);
                    $issues++;
                }
            }
        }

        return $issues;
    }

    /**
     * @return array<int, string>
     */
    private function placeholders(string $value): array
    {
        preg_match_all('/\{[a-zA-Z0-9_]+\}/', $value, $matches);
        $placeholders = array_values(array_unique($matches[0]));
        sort($placeholders);

        return $placeholders;
    }

    /**
     * @param  array<int, string>  $fields
     */
    private function repair(TranslateService $translator, Model $record, array $fields): void
    {
        $remainingFields = $fields;

        for ($attempt = 1; $attempt <= self::MAX_REPAIR_ATTEMPTS; $attempt++) {
            $source = [];

            foreach ($remainingFields as $field) {
                $value = (array) $record->getAttribute($field);
                $source[$field] = (string) ($value['tr'] ?? '');
            }

            $translations = $translator->translateFields($source);

            foreach ($remainingFields as $field) {
                $record->setAttribute($field, array_merge(
                    (array) $record->getAttribute($field),
                    $translations[$field] ?? [],
                ));
            }

            $record->save();
            $record->refresh();

            $missing = TranslationStatus::missingForRecord($record, $fields);

            if ($missing === []) {
                return;
            }

            $remainingFields = array_keys($missing);
        }

        $missing = TranslationStatus::missingForRecord($record, $fields);

        throw new RuntimeException(
            'Gemini '.self::MAX_REPAIR_ATTEMPTS.' denemeden sonra bazı dilleri eksik bıraktı: '
            .TranslationStatus::summary($missing),
        );
    }

    /**
     * @return array{int, int, int} incomplete, repaired, failed
     */
    private function checkSiteSetting(TranslateService $translator, bool $fix): array
    {
        $record = SiteSetting::query()->find('storefront');

        if (! $record) {
            return [0, 0, 0];
        }

        $missing = $this->missingSiteSettingFields((array) $record->value);

        if ($missing === []) {
            return [0, 0, 0];
        }

        $this->line('SiteSetting #storefront → '.TranslationStatus::summary($missing));

        if (! $fix) {
            return [1, 0, 0];
        }

        try {
            $this->repairSiteSetting($translator, $record, array_keys($missing));
            $this->info('  tamamlandı');

            return [1, 1, 0];
        } catch (Throwable $exception) {
            $this->error('  hata: '.$exception->getMessage());

            return [1, 0, 1];
        }
    }

    /**
     * @param  array<int, string>  $fields
     */
    private function repairSiteSetting(
        TranslateService $translator,
        SiteSetting $record,
        array $fields,
    ): void {
        $remainingFields = $fields;

        for ($attempt = 1; $attempt <= self::MAX_REPAIR_ATTEMPTS; $attempt++) {
            $value = (array) $record->value;
            $source = [];

            foreach ($remainingFields as $field) {
                $source[$field] = (string) ($value['tr'][$field] ?? '');
            }

            $translations = $translator->translateFields($source);

            foreach ($translations as $field => $localizedValues) {
                foreach ($localizedValues as $locale => $text) {
                    if (Storefront::hasLocale($locale)) {
                        $value[$locale][$field] = $text;
                    }
                }
            }

            $record->value = $value;
            $record->save();
            $record->refresh();

            $missing = $this->missingSiteSettingFields((array) $record->value, $fields);

            if ($missing === []) {
                return;
            }

            $remainingFields = array_keys($missing);
        }

        throw new RuntimeException(
            'Gemini '.self::MAX_REPAIR_ATTEMPTS.' denemeden sonra site ayarlarını eksik bıraktı: '
            .TranslationStatus::summary(
                $this->missingSiteSettingFields((array) $record->value, $fields),
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  array<int, string>|null  $fields
     * @return array<string, array<int, string>>
     */
    private function missingSiteSettingFields(array $value, ?array $fields = null): array
    {
        $missing = [];

        foreach ($fields ?? self::SITE_SETTING_FIELDS as $field) {
            $localized = ['tr' => (string) ($value['tr'][$field] ?? '')];

            foreach (TranslationStatus::expectedLocales() as $locale) {
                $localized[$locale] = (string) ($value[$locale][$field] ?? '');
            }

            $locales = TranslationStatus::missingLocales($localized);

            if ($locales !== []) {
                $missing[$field] = $locales;
            }
        }

        return $missing;
    }
}
