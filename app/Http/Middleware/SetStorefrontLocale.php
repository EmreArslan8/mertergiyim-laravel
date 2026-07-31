<?php

namespace App\Http\Middleware;

use App\Services\DictionaryService;
use App\Services\StorefrontRepository;
use App\Support\Storefront;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * app/[locale]/layout.tsx karşılığı: locale doğrulaması + tüm vitrin
 * sayfalarının paylaştığı header/footer verisi.
 */
class SetStorefrontLocale
{
    public function __construct(
        private DictionaryService $dictionary,
        private StorefrontRepository $repository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) $request->route('locale');

        abort_unless(Storefront::hasLocale($locale), 404);

        $chrome = $this->repository->chrome();

        $activeLanguages = array_values(array_filter(
            $chrome['languages'],
            fn ($language) => Storefront::hasLocale($language->code),
        ));

        // Kaynak projede aktif olmayan dilin sayfası 404 döner.
        abort_unless(
            collect($activeLanguages)->contains(fn ($language) => $language->code === $locale),
            404,
        );

        app()->setLocale($locale);

        // Panelden gelen dinamik ayarlarda aktif dil eksikse kaynak Türkçe
        // değerini kullan. Böylece fallback de kod içindeki sabitlerden değil
        // veritabanından gelir.
        $localeSettings = array_replace(
            (array) ($chrome['settingValue']['tr'] ?? []),
            (array) ($chrome['settingValue'][$locale] ?? []),
        );
        $siteSettings = $chrome['settingValue']['general'] ?? [];
        $timezone = (string) ($siteSettings['timezone'] ?? config('app.timezone'));
        if (in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        $siteName = $localeSettings['siteName']
            ?? $this->dictionary->all($locale)['common']['brand']
            ?? config('storefront.brand_name');

        View::share([
            'locale' => $locale,
            'dir' => Storefront::isRtl($locale) ? 'rtl' : 'ltr',
            'messages' => $this->dictionary->all($locale),
            'languages' => $activeLanguages,
            'categories' => $chrome['categories'],
            'headerLinks' => array_values(array_filter($chrome['links'], fn ($link) => $link->location === 'header')),
            'footerLinks' => array_values(array_filter($chrome['links'], fn ($link) => $link->location === 'footer')),
            'siteName' => $siteName,
            'siteSettings' => $siteSettings,
            'footerSettings' => array_filter(
                $localeSettings,
                fn ($key) => str_starts_with($key, 'footer')
                    || str_starts_with($key, 'contact')
                    || str_starts_with($key, 'seo')
                    || str_starts_with($key, 'maintenance')
                    || str_starts_with($key, 'whatsapp')
                    || str_starts_with($key, 'order')
                    || $key === 'copyright',
                ARRAY_FILTER_USE_KEY,
            ),
        ]);

        if ((bool) ($siteSettings['maintenanceMode'] ?? false)) {
            return response()
                ->view('storefront.maintenance', status: 503)
                ->header('Retry-After', '3600');
        }

        return $next($request);
    }
}
