<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Color;
use App\Models\ContentPage;
use App\Models\HeroSlide;
use App\Models\Media;
use App\Models\Product;
use App\Models\SiteLink;
use App\Models\Size;
use App\Services\TranslateService;
use App\Support\TranslationStatus;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
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
        Media::class => ['title', 'alt', 'caption'],
        SiteLink::class => ['label'],
    ];

    public function handle(TranslateService $translator): int
    {
        $fix = (bool) $this->option('fix');
        $only = (string) $this->option('model');

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

        if ($incomplete === 0) {
            $this->info('Tüm çok dilli alanlar 9 yabancı dilde eksiksiz.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn($incomplete.' kayıtta eksik çeviri var.');

        if ($fix) {
            $this->line('Tamamlanan: '.$repaired.', başarısız: '.$failed);
        }

        // Eksik varken 1 döner; CI/deploy adımı buna bakarak durabilir.
        return $failed > 0 || ! $fix ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $fields
     */
    private function repair(TranslateService $translator, Model $record, array $fields): void
    {
        $source = [];

        foreach ($fields as $field) {
            $value = (array) $record->getAttribute($field);
            $source[$field] = (string) ($value['tr'] ?? '');
        }

        $translations = $translator->translateFields($source);

        foreach ($fields as $field) {
            $record->setAttribute($field, array_merge(
                (array) $record->getAttribute($field),
                $translations[$field] ?? [],
            ));
        }

        $record->save();
    }
}
