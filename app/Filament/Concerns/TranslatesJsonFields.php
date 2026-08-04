<?php

namespace App\Filament\Concerns;

use App\Filament\Support\Multilingual;
use App\Services\TranslateService;
use App\Support\Storefront;
use App\Support\TranslationStatus;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Panelde yalnızca Türkçe alan gösterilir; kayıt anında Türkçe değer(ler)
 * Gemini ile 9 dile çevrilip jsonb'nin ilgili anahtarlarına yazılır.
 *
 * Kurallar:
 * - Türkçe metin değişmediyse ve tüm diller doluysa çeviri çağrısı yapılmaz.
 * - Eksik dil varsa Türkçe değişmemiş olsa bile çeviriler otomatik tamamlanır.
 * - Değişen tüm alanlar TEK Gemini isteğinde çevrilir.
 * - Gemini hatası veya eksik dil kaydı durdurur ve işlemi geri alır.
 *
 * Varsayılan davranış "kolon -> { locale: metin }" yapısı içindir
 * (products.name, hero_slides.title ...). Farklı jsonb şekli olan kaynaklar
 * currentTrValue/originalTrValue/applyTranslatedValues metotlarını ezer.
 */
trait TranslatesJsonFields
{
    /** Aynı Livewire isteğinde başarısız dış servise tekrar tekrar gitme. */
    protected bool $automaticTranslationUnavailable = false;

    /** Çok alanlı/repeater kayıtlarında toplam HTTP süresini PHP sınırının altında tutar. */
    protected ?float $automaticTranslationStartedAt = null;

    /** @var array<string, array<string, string>>|null Türkçe alt metin => çeviriler */
    protected ?array $automaticImageAltTranslationBatch = null;

    /**
     * Otomatik çevrilecek alanlar.
     *
     * @return array<string, string> alan => panelde görünen etiket
     */
    abstract protected function translatableJsonFields(): array;

    /**
     * Kaydın sayfadan değil dışarıdan geldiği durumlar (relation manager
     * aksiyonları) için geçici kayıt.
     */
    protected ?Model $translationRecordOverride = null;

    /**
     * İlişki repeater'ları aynı çeviri akışını farklı alanlarla kullanabilir.
     *
     * @var array<string, string>|null
     */
    protected ?array $translationFieldsOverride = null;

    /**
     * Relation manager aksiyonları için: ilgili satır kaydı elle verilir.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function fillAutomaticTranslationsFor(array $data, ?Model $record): array
    {
        $this->translationRecordOverride = $record;

        try {
            return $this->fillAutomaticTranslations($data);
        } finally {
            $this->translationRecordOverride = null;
        }
    }

    /**
     * Üst sayfadaki ilişki repeater'ları için alan listesini geçici değiştir.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $fields
     * @return array<string, mixed>
     */
    public function fillAutomaticTranslationsForFields(
        array $data,
        ?Model $record,
        array $fields,
    ): array {
        $this->translationRecordOverride = $record;
        $this->translationFieldsOverride = $fields;

        try {
            return $this->fillAutomaticTranslations($data);
        } finally {
            $this->translationRecordOverride = null;
            $this->translationFieldsOverride = null;
        }
    }

    /**
     * Ürün repeater'ındaki bütün görsel alt metinlerini tek Gemini isteğinde
     * çevirir. Her satırın ayrı HTTP isteği açması PHP zaman aşımına yol açıyordu.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function fillAutomaticImageAltTranslations(array $data, ?Model $record): array
    {
        $turkish = trim((string) Arr::get($data, 'alt.tr', ''));

        if ($turkish === '') {
            Arr::set($data, 'alt', array_merge(
                $this->modelJsonValue($record, 'alt'),
                ['tr' => ''],
            ));

            return $data;
        }

        if ($this->automaticImageAltTranslationBatch === null) {
            $this->automaticImageAltTranslationBatch = [];
            $texts = [];
            $textKeys = [];

            // Değişmeyen ve 9 dili zaten tamam olan mevcut görsellerin
            // çevirilerini yeniden kullan; yalnızca yeni/değişmiş/eksik
            // alternatif metinler toplu isteğe girsin.
            $pageRecord = $this->translationRecord();

            if ($pageRecord && method_exists($pageRecord, 'images')) {
                foreach ($pageRecord->images()->get() as $existingImage) {
                    $existing = $this->modelJsonValue($existingImage, 'alt');
                    $existingTr = trim((string) ($existing['tr'] ?? ''));

                    if ($existingTr !== '' && TranslationStatus::missingLocales($existing) === []) {
                        unset($existing['tr']);
                        $this->automaticImageAltTranslationBatch[$existingTr] = $existing;
                    }
                }
            }

            foreach ((array) data_get($this, 'data.images', []) as $image) {
                $text = trim((string) data_get($image, 'alt.tr', ''));

                if ($text === ''
                    || isset($textKeys[$text])
                    || isset($this->automaticImageAltTranslationBatch[$text])) {
                    continue;
                }

                $key = $texts === [] ? 'alt' : 'alt_'.(count($texts) + 1);
                $texts[$key] = $text;
                $textKeys[$text] = $key;
            }

            if ($texts !== []) {
                try {
                    $translated = app(TranslateService::class)->translateFields($texts);
                } catch (Throwable $exception) {
                    // Görsel alt metni yardımcı içeriktir. Çeviri servisi geçici
                    // olarak çalışmazsa ürün kaydını engelleme; Türkçe alt metni
                    // kaydet ve diğer dilleri daha sonra tamamlamaya bırak.
                    Log::warning('Görsel alt metinleri çevrilemedi; kayıt Türkçe alt metinle sürdürüldü.', [
                        'record' => $this->translationRecord()?->getKey(),
                        'error' => $exception->getMessage(),
                    ]);

                    $translated = [];
                }

                foreach ($textKeys as $text => $key) {
                    $values = $translated[$key] ?? [];
                    $this->automaticImageAltTranslationBatch[$text] = is_array($values) ? $values : [];
                }
            }
        }

        Arr::set($data, 'alt', array_merge(
            $this->modelJsonValue($record, 'alt'),
            $this->automaticImageAltTranslationBatch[$turkish] ?? [],
            ['tr' => $turkish],
        ));

        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->fillAutomaticTranslations($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->fillAutomaticTranslations($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function fillAutomaticTranslations(array $data): array
    {
        /** @var array<string, string> $turkish */
        $turkish = [];
        /** @var array<string, string> $changed */
        $changed = [];

        foreach (array_keys($this->resolvedTranslatableJsonFields()) as $field) {
            $value = $this->currentTrValue($data, $field);

            // Alan bu formda yoksa dokunma.
            if ($value === null) {
                continue;
            }

            $turkish[$field] = $value;

            if ($value !== '' && (
                $this->trValueHasChanged($value, $this->originalTrValue($field))
                || TranslationStatus::missingLocales($this->originalJsonValue($field)) !== []
            )) {
                $changed[$field] = $value;
            }
        }

        $translations = $changed === [] ? [] : $this->translate($changed);

        foreach ($turkish as $field => $value) {
            $data = $this->applyTranslatedValues($data, $field, $value, $translations[$field] ?? []);
        }

        if ($changed !== []) {
            $this->warnAboutIncompleteTranslations($data, array_keys($changed));
        }

        return $data;
    }

    /**
     * Gemini başarılı yanıt verse bile bütün diller tamamlanmadan kayıt yapılmaz.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $fields
     */
    protected function warnAboutIncompleteTranslations(array $data, array $fields): void
    {
        $labels = $this->resolvedTranslatableJsonFields();
        $missing = [];

        foreach ($fields as $field) {
            $locales = TranslationStatus::missingLocales($this->translatedValueFor($data, $field));

            if ($locales !== []) {
                $missing[] = ($labels[$field] ?? $field).': '
                    .implode(', ', array_map(Multilingual::localeLabel(...), $locales));
            }
        }

        if ($missing === []) {
            return;
        }

        Notification::make()
            ->title('Kayıt yapılmadı: çeviriler eksik.')
            ->body(implode(' • ', $missing).' — Lütfen tekrar deneyin.')
            ->danger()
            ->persistent()
            ->send();

        throw (new Halt)->rollBackDatabaseTransaction();
    }

    /**
     * Eksik dil kontrolü için alanın kaydedilecek son hâli.
     *
     * @param  array<string, mixed>  $data
     */
    protected function translatedValueFor(array $data, string $field): mixed
    {
        return Arr::get($data, $field);
    }

    /**
     * @param  array<string, string>  $changed
     * @return array<string, array<string, string>>
     */
    protected function translate(array $changed): array
    {
        $this->automaticTranslationStartedAt ??= microtime(true);

        // Tek istek timeout 20 sn + 1 retry (~40 sn) olabildiği için toplam
        // bütçe buna göre; aksi hâlde retry tamamlanmadan kayıt kesilirdi.
        if ($this->automaticTranslationUnavailable
            || (microtime(true) - $this->automaticTranslationStartedAt) >= 45) {
            Notification::make()
                ->title('Kayıt yapılmadı: çeviri servisi zaman aşımına uğradı.')
                ->body('Hiçbir içerik eksik çeviriyle kaydedilmedi. Lütfen tekrar deneyin.')
                ->danger()
                ->persistent()
                ->send();

            throw (new Halt)->rollBackDatabaseTransaction();
        }

        try {
            return app(TranslateService::class)->translateFields($changed);
        } catch (Throwable $exception) {
            $this->automaticTranslationUnavailable = true;

            Log::warning('Otomatik çeviri yapılamadı.', [
                'fields' => array_keys($changed),
                'record' => $this->translationRecord()?->getKey(),
                'error' => $exception->getMessage(),
            ]);

            Notification::make()
                ->title('Kayıt yapılmadı: otomatik çeviri başarısız.')
                ->body($exception->getMessage().' — Lütfen tekrar deneyin.')
                ->danger()
                ->persistent()
                ->send();

            throw (new Halt)->rollBackDatabaseTransaction();
        }
    }

    /**
     * Formdan gelen Türkçe değer. Alan formda yoksa null.
     *
     * @param  array<string, mixed>  $data
     */
    protected function currentTrValue(array $data, string $field): ?string
    {
        if (! Arr::has($data, $field)) {
            return null;
        }

        return trim((string) (Arr::get($data, $field.'.tr') ?? ''));
    }

    /**
     * Türkçe metin gerçekten değişti mi?
     *
     * Zengin editör alanları (ürün açıklaması) eski düz metin kayıtlarını
     * "<p>…</p>" hâline getirir. Yalnızca biçim farkı 10 dilin boşuna yeniden
     * çevrilmesine yol açmasın diye karşılaştırma düz metin üzerinden yapılır.
     */
    protected function trValueHasChanged(string $value, ?string $original): bool
    {
        if ($original === null) {
            return true;
        }

        if ($value === $original) {
            return false;
        }

        return Storefront::plainText($value, 'tr') !== Storefront::plainText($original, 'tr');
    }

    /**
     * Kayıttaki (henüz güncellenmemiş) Türkçe değer. Yeni kayıtta null.
     */
    protected function originalTrValue(string $field): ?string
    {
        $existing = $this->originalJsonValue($field);

        return isset($existing['tr']) ? trim((string) $existing['tr']) : null;
    }

    /**
     * Mevcut çevirileri koruyarak yeni değerleri jsonb'ye yazar.
     *
     * Türkçe metin boşaltılsa bile eski çeviriler silinmez; vitrin zaten
     * tr'ye düştüğü için veri kaybı riski almaya gerek yok.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $translations
     * @return array<string, mixed>
     */
    protected function applyTranslatedValues(array $data, string $field, string $tr, array $translations): array
    {
        Arr::set($data, $field, array_merge(
            $this->originalJsonValue($field),
            $translations,
            ['tr' => $tr],
        ));

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function originalJsonValue(string $field): array
    {
        return $this->modelJsonValue($this->translationRecord(), $field);
    }

    /** @return array<string, mixed> */
    protected function modelJsonValue(?Model $record, string $field): array
    {

        if (! $record) {
            return [];
        }

        $value = $record->getOriginal($field);

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? $value : [];
    }

    /**
     * Düzenleme sayfasında kayıt, oluşturma sayfasında null.
     */
    protected function translationRecord(): ?Model
    {
        if ($this->translationRecordOverride) {
            return $this->translationRecordOverride;
        }

        return method_exists($this, 'getRecord') ? $this->getRecord() : null;
    }

    /**
     * Normal sayfa alanları veya ilişki repeater'ının geçici alanları.
     *
     * @return array<string, string>
     */
    protected function resolvedTranslatableJsonFields(): array
    {
        return $this->translationFieldsOverride ?? $this->translatableJsonFields();
    }
}
