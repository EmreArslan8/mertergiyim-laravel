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
                'siteName' => (string) config('storefront.brand_name'),
                'footerBrand' => (string) config('storefront.brand_name'),
                'footerDescription' => '<p>Yeni sezon ürünleri ve güncel koleksiyonlar.</p>',
                'footerAddress' => '',
                'footerInfoTitle' => 'Bilgilendirmeler',
                'copyright' => '© '.date('Y').' '.config('storefront.brand_name').'. Tüm hakları saklıdır.',
                'contactTitle' => 'İletişim',
                'contactDescription' => '<p>Ürünler ve sipariş süreçleri için bizimle iletişime geçebilirsiniz.</p>',
                'contactAddress' => '',
                'seoTitle' => (string) config('storefront.brand_name'),
                'seoDescription' => '',
                'seoKeywords' => '',
                'whatsappMessage' => 'Merhaba, {product} ürünü hakkında bilgi almak istiyorum.',
                'maintenanceTitle' => 'Kısa bir bakımdayız',
                'maintenanceMessage' => '<p>Mağazamızı iyileştiriyoruz. Lütfen kısa süre sonra tekrar ziyaret edin.</p>',
                'orderSuccessText' => '<p>Siparişiniz mağazaya iletildi. Onay için sizinle telefon üzerinden iletişime geçilecektir.</p>',
                'homeCategoryTitle' => 'KATEGORİLER',
                'homeAllCategoriesLabel' => 'TÜMÜ',
                'homeCollectionLabel' => 'Koleksiyon',
                'homeFeaturedTitle' => 'Öne Çıkan Ürünler',
                'homeOrderNotice' => 'Yalnızca sipariş sistemiyle satış yapılır. Ödeme ve teslimat bilgileri sipariş sonrası teyit edilir.',
                'homeEmptyTitle' => 'Henüz ürün eklenmedi.',
                'homeEmptyDescription' => 'Yeni ürünler çok yakında burada olacak. Lütfen daha sonra tekrar göz at.',
                'homeFilterEmptyTitle' => 'Bu kategoride ürün bulunamadı.',
                'homeFilterEmptyDescription' => 'Başka bir kategori seçebilir veya tüm ürünleri yeniden görüntüleyebilirsin.',
                'homeShowAllProductsLabel' => 'Tüm ürünleri göster',
                'homeSeoTitle' => (string) config('storefront.brand_name'),
                'homeSeoDescription' => '',
                'homeSeoKeywords' => '',
            ],
            'general' => [
                'siteUrl' => (string) config('storefront.site_url'),
                'defaultLocale' => (string) config('storefront.default_locale'),
                'timezone' => (string) config('app.timezone'),
                'maintenanceMode' => false,
                'siteLogo' => null,
                'favicon' => null,
                'socialShareImage' => null,
                'seoShareImage' => null,
                'whatsappNumber' => (string) config('storefront.whatsapp_number'),
                'contactPhone' => '',
                'contactEmail' => '',
                'socialLinks' => [],
                'googleMapsIframe' => '',
                'salesMode' => 'wholesale',
                'orderNotificationChatId' => '',
                'minimumOrderAmount' => 0,
                'homeProductLimit' => 12,
                'homeSeoShareImage' => null,
                'pricesIncludeTax' => true,
                'searchIndexingEnabled' => true,
                'googleSiteVerification' => '',
                'googleAnalyticsId' => '',
                'googleTagManagerId' => '',
                'metaPixelId' => '',
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
