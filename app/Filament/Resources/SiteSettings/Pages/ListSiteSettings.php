<?php

namespace App\Filament\Resources\SiteSettings\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\SiteSettings\Schemas\SiteSettingForm;
use App\Filament\Resources\SiteSettings\SiteSettingResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Arr;

class ListSiteSettings extends ListRecords
{
    use TranslatesJsonFields {
        fillAutomaticTranslations as protected baseFillAutomaticTranslations;
    }

    protected static string $resource = SiteSettingResource::class;

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
