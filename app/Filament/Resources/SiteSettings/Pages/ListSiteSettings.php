<?php

namespace App\Filament\Resources\SiteSettings\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\SiteSettings\Schemas\SiteSettingForm;
use App\Filament\Resources\SiteSettings\SiteSettingResource;
use App\Models\SiteSetting;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Arr;

class ListSiteSettings extends ListRecords
{
    use TranslatesJsonFields {
        fillAutomaticTranslations as protected baseFillAutomaticTranslations;
    }

    protected static string $resource = SiteSettingResource::class;

    public function mount(): void
    {
        parent::mount();

        SiteSetting::query()->firstOrCreate(
            ['key' => 'storefront'],
            [
                'value' => self::defaultSettings(),
                'updated_at' => now(),
            ],
        );

        $this->redirect(
            SiteSettingResource::getUrl('edit', ['record' => 'storefront']),
            navigate: true,
        );
    }

    private static function defaultSettings(): array
    {
        return [
            'tr' => [
                'siteName' => 'Merter Giyim',
                'footerBrand' => 'Merter Giyim',
                'footerDescription' => 'Merter’den dünyaya toptan kadın giyim.',
                'footerAddress' => 'Merter / İstanbul',
                'footerInfoTitle' => 'Bilgilendirmeler',
                'copyright' => '© '.date('Y').' Merter Giyim. Tüm hakları saklıdır.',
            ],
            'general' => [
                'siteLogo' => null,
                'whatsappNumber' => (string) config('storefront.whatsapp_number'),
                'contactPhone' => '',
                'contactEmail' => '',
                'instagramUrl' => '',
            ],
        ];
    }

    protected function translatableJsonFields(): array
    {
        return SiteSettingForm::FIELDS;
    }

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
