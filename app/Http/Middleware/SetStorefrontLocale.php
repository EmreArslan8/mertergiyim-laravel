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

        $localeSettings = $chrome['settingValue'][$locale] ?? [];
        $siteSettings = $chrome['settingValue']['general'] ?? [];
        $siteName = $localeSettings['siteName']
            ?? $this->dictionary->all($locale)['common']['brand']
            ?? 'Merter Giyim';

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
                fn ($key) => str_starts_with($key, 'footer') || $key === 'copyright',
                ARRAY_FILTER_USE_KEY,
            ),
        ]);

        return $next($request);
    }
}
