<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Throwable;

class BrandSettings
{
    private const CACHE_KEY = 'storefront:brand-settings';

    public static function name(?string $locale = null): string
    {
        $value = self::value();
        $locale ??= (string) app()->getLocale();

        return trim((string) (
            $value[$locale]['siteName']
            ?? $value['tr']['siteName']
            ?? config('storefront.brand_name')
        ));
    }

    public static function logoUrl(): ?string
    {
        return self::mediaUrl('siteLogo');
    }

    public static function faviconUrl(): ?string
    {
        return self::mediaUrl('favicon') ?? self::logoUrl();
    }

    public static function general(string $key, mixed $default = null): mixed
    {
        return self::value()['general'][$key] ?? $default;
    }

    public static function localized(string $key, ?string $locale = null, mixed $default = null): mixed
    {
        $value = self::value();
        $locale ??= (string) app()->getLocale();

        return $value[$locale][$key] ?? $value['tr'][$key] ?? $default;
    }

    public static function siteUrl(): string
    {
        $url = trim((string) self::general('siteUrl', config('storefront.site_url')));

        return rtrim($url !== '' ? $url : (string) config('storefront.site_url'), '/');
    }

    public static function defaultLocale(): string
    {
        $locale = (string) self::general('defaultLocale', config('storefront.default_locale'));

        return Storefront::hasLocale($locale) ? $locale : (string) config('storefront.default_locale');
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function value(): array
    {
        try {
            return Cache::rememberForever(
                self::CACHE_KEY,
                fn (): array => (array) (SiteSetting::query()->find('storefront')?->value ?? []),
            );
        } catch (Throwable) {
            // Kurulum/migration aşamasında panel marka bilgisi güvenli varsayılana düşer.
            return [];
        }
    }

    private static function mediaUrl(string $key): ?string
    {
        $path = trim((string) (self::value()['general'][$key] ?? ''));

        if ($path === '') {
            return null;
        }

        $url = Storefront::storageUrl('site', $path);

        return $url !== '' ? $url : null;
    }
}
