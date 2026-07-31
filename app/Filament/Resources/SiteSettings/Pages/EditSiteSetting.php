<?php

namespace App\Filament\Resources\SiteSettings\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Currencies\CurrencyResource;
use App\Filament\Resources\Languages\LanguageResource;
use App\Filament\Resources\SiteSettings\Schemas\SiteSettingForm;
use App\Filament\Resources\SiteSettings\SiteSettingResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;

class EditSiteSetting extends EditRecord
{
    use TranslatesJsonFields {
        fillAutomaticTranslations as protected baseFillAutomaticTranslations;
    }

    protected static string $resource = SiteSettingResource::class;

    public function getTitle(): string
    {
        return 'Site Ayarları';
    }

    public function getSubheading(): string
    {
        return 'Genel · Marka · Satış · SEO · İletişim · Sosyal · Analitik';
    }

    /**
     * Diller ve Para Birimi sidebar'da yer kaplamasın diye bu ekranın üstüne
     * bağlandı: ikisi de nadiren dokunulan sistem ayarları.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('languages')
                ->label('Diller')
                ->icon(Heroicon::OutlinedLanguage)
                ->color('gray')
                ->visible(fn (): bool => LanguageResource::canAccess())
                ->url(fn (): string => LanguageResource::getUrl()),
            Action::make('currencies')
                ->label('Para Birimi')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('gray')
                ->visible(fn (): bool => CurrencyResource::canAccess())
                ->url(fn (): string => CurrencyResource::getUrl()),
        ];
    }

    /**
     * site_settings.updated_at kolonu Eloquent timestamp'ı olmadığı için
     * elle tazelenir.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->fillAutomaticTranslations($data);
        $data['updated_at'] = now();

        return $data;
    }

    protected function translatableJsonFields(): array
    {
        return SiteSettingForm::FIELDS;
    }

    /**
     * Burada jsonb yapısı { locale: { alan: değer } } olduğu için önce mevcut
     * diller korunacak şekilde birleştirilir.
     */
    protected function fillAutomaticTranslations(array $data): array
    {
        $merged = $this->originalJsonValue('value');

        foreach ((array) ($data['value'] ?? []) as $locale => $fields) {
            $merged[$locale] = array_merge((array) ($merged[$locale] ?? []), (array) $fields);
        }

        $data['value'] = $merged;

        return $this->baseFillAutomaticTranslations($data);
    }

    protected function currentTrValue(array $data, string $field): ?string
    {
        if (! Arr::has($data, 'value.tr')) {
            return null;
        }

        return trim((string) (Arr::get($data, 'value.tr.'.$field) ?? ''));
    }

    protected function originalTrValue(string $field): ?string
    {
        $value = Arr::get($this->originalJsonValue('value'), 'tr.'.$field);

        return $value === null ? null : trim((string) $value);
    }

    /**
     * Eksik dil kontrolü value[locale][field] yapısı üzerinden yapılır.
     */
    protected function translatedValueFor(array $data, string $field): mixed
    {
        $value = [];

        foreach ((array) ($data['value'] ?? []) as $locale => $fields) {
            $value[$locale] = (string) (($fields[$field] ?? '') ?: '');
        }

        return $value;
    }

    protected function applyTranslatedValues(array $data, string $field, string $tr, array $translations): array
    {
        $data['value']['tr'][$field] = $tr;

        foreach ($translations as $locale => $text) {
            $data['value'][$locale][$field] = $text;
        }

        return $data;
    }
}
