<?php

namespace App\Filament\Concerns;

use App\Filament\Support\Multilingual;
use App\Services\TranslateService;
use App\Support\Storefront;
use App\Support\TranslationStatus;
use Filament\Notifications\Notification;
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
 * - Gemini hatası kaydı bozmaz: tr yazılır, eski çeviriler kalır, kullanıcı uyarılır.
 *
 * Varsayılan davranış "kolon -> { locale: metin }" yapısı içindir
 * (products.name, hero_slides.title ...). Farklı jsonb şekli olan kaynaklar
 * currentTrValue/originalTrValue/applyTranslatedValues metotlarını ezer.
 */
trait TranslatesJsonFields
{
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

        foreach (array_keys($this->translatableJsonFields()) as $field) {
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
     * Gemini istek başarılı olsa bile bazı dilleri eksik döndürebiliyor.
     * Kayıt engellenmez ama hangi dillerin eksik kaldığı panelde bildirilir.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $fields
     */
    protected function warnAboutIncompleteTranslations(array $data, array $fields): void
    {
        $labels = $this->translatableJsonFields();
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
            ->title('Bazı diller çevrilemedi, kayıt tamamlandı.')
            ->body(implode(' • ', $missing).' — Kaydı tekrar kaydederek ya da "php artisan translations:check --fix" ile tamamlayabilirsiniz.')
            ->warning()
            ->persistent()
            ->send();
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
        try {
            return app(TranslateService::class)->translateFields($changed);
        } catch (Throwable $exception) {
            Log::warning('Otomatik çeviri yapılamadı.', [
                'fields' => array_keys($changed),
                'record' => $this->translationRecord()?->getKey(),
                'error' => $exception->getMessage(),
            ]);

            Notification::make()
                ->title('Otomatik çeviri yapılamadı, metinler Türkçe kaydedildi.')
                ->body($exception->getMessage())
                ->warning()
                ->persistent()
                ->send();

            return [];
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
        $record = $this->translationRecord();

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
}
