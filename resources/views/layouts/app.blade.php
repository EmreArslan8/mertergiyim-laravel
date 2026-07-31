@php
    use App\Support\Storefront;

    $siteUrl = rtrim((string) ($siteSettings['siteUrl'] ?? config('storefront.site_url')), '/');
    $defaultLocale = \App\Support\BrandSettings::defaultLocale();
    $canonicalPath = $canonicalPath ?? '/'.$locale;
    $alternatePath = $alternatePath ?? fn (string $code) => '/'.$code;
    $defaultSeoTitle = trim((string) ($footerSettings['seoTitle'] ?? '')) ?: $siteName;
    $defaultSeoDescription = trim((string) ($footerSettings['seoDescription'] ?? ''))
        ?: ($messages['meta']['description'] ?? '');
    $defaultSeoKeywords = trim((string) ($footerSettings['seoKeywords'] ?? ''))
        ?: ($messages['meta']['keywords'] ?? '');
    $metaTitle = $metaTitle ?? $defaultSeoTitle;
    $metaDescription = $metaDescription ?? $defaultSeoDescription;
    $metaKeywords = $metaKeywords ?? $defaultSeoKeywords;
    $defaultOgPath = trim((string) ($siteSettings['seoShareImage'] ?? $siteSettings['socialShareImage'] ?? ''));
    $ogImage = $ogImage ?? ($defaultOgPath !== '' ? Storefront::storageUrl('site', $defaultOgPath) : null);
    $faviconPath = trim((string) ($siteSettings['favicon'] ?? $siteSettings['siteLogo'] ?? ''));
    $faviconUrl = $faviconPath !== '' ? Storefront::storageUrl('site', $faviconPath) : '/icon.png';
    $googleAnalyticsId = preg_match('/^G-[A-Z0-9]+$/i', (string) ($siteSettings['googleAnalyticsId'] ?? ''))
        ? strtoupper((string) $siteSettings['googleAnalyticsId'])
        : null;
    $metaPixelId = preg_match('/^[0-9]+$/', (string) ($siteSettings['metaPixelId'] ?? ''))
        ? (string) $siteSettings['metaPixelId']
        : null;
    $googleTagManagerId = preg_match('/^GTM-[A-Z0-9]+$/i', (string) ($siteSettings['googleTagManagerId'] ?? ''))
        ? strtoupper((string) $siteSettings['googleTagManagerId'])
        : null;
    $indexingEnabled = (bool) ($siteSettings['searchIndexingEnabled'] ?? true);
    $googleSiteVerification = trim((string) ($siteSettings['googleSiteVerification'] ?? ''));
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    @if ($metaKeywords)
        <meta name="keywords" content="{{ $metaKeywords }}">
    @endif

    <link rel="canonical" href="{{ $siteUrl.$canonicalPath }}">
    @foreach (Storefront::locales() as $code)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $siteUrl.$alternatePath($code) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $siteUrl.$alternatePath($defaultLocale) }}">
    <meta name="robots" content="{{ $indexingEnabled ? 'index,follow' : 'noindex,nofollow' }}">
    @if ($googleSiteVerification)
        <meta name="google-site-verification" content="{{ $googleSiteVerification }}">
    @endif

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName ?? config('storefront.brand_name') }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:locale" content="{{ $locale }}">
    <meta property="og:url" content="{{ $siteUrl.$canonicalPath }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">

    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">

    {{-- Tüm görseller Supabase Storage'dan geliyor: TLS el sıkışmasını öne al. --}}
    @php ($storageOrigin = parse_url((string) config('storefront.storage_url'), PHP_URL_SCHEME).'://'.parse_url((string) config('storefront.storage_url'), PHP_URL_HOST))
    <link rel="preconnect" href="{{ $storageOrigin }}" crossorigin>

    {{-- LCP: hero inline background-image olarak basılıyor; preload ile isteği öne çekiyoruz. --}}
    @if (! empty($heroImage))
        <link rel="preload" as="image" href="{{ $heroImage }}" fetchpriority="high">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- italic ekseni bilerek yok: kaynak index.html ile birebir sahte eğim (synthetic oblique) --}}
    {{-- preload: swap penceresini kısaltıp başlıklardaki font kaynaklı kaymayı azaltır --}}
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Kaynak projedeki import sırası korunuyor. --}}
    <link rel="stylesheet" href="/css/storefront.css?v=20260730-1">
    <link rel="stylesheet" href="/css/styles.css?v=20260731-6">
    <link rel="stylesheet" href="/css/hero-override.css?v=20260731-6">
    <link rel="stylesheet" href="/css/category-filter.css">
    <link rel="stylesheet" href="/css/page-heading.css?v=20260731-6">
    <link rel="stylesheet" href="/css/site-links.css">
    @stack('styles')

    {{-- Aynı origindeki sayfaları hover/dokunuş anında önden çeker: geçiş beklemesini kısaltır.
         prerender değil prefetch — analytics/pixel sayımlarını bozmaması için. --}}
    <script type="speculationrules">
    {
      "prefetch": [{
        "where": {
          "and": [
            {"href_matches": "/*"},
            {"not": {"href_matches": "/admin/*"}},
            {"not": {"selector_matches": "[data-no-prefetch]"}}
          ]
        },
        "eagerness": "moderate"
      }]
    }
    </script>

    @if ($googleTagManagerId)
        <script>
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer',@json($googleTagManagerId));
        </script>
    @endif
    @if ($googleAnalyticsId)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ rawurlencode($googleAnalyticsId) }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($googleAnalyticsId));
        </script>
    @endif
    @if ($metaPixelId)
        <script>
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
            n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}
            (window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', @json($metaPixelId));
            fbq('track', 'PageView');
        </script>
    @endif
</head>
<body class="{{ $bodyClass ?? '' }}">
    @if ($googleTagManagerId)
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ rawurlencode($googleTagManagerId) }}"
            height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>
    @endif
    @if ($metaPixelId)
        <noscript><img height="1" width="1" alt="" style="display:none"
            src="https://www.facebook.com/tr?id={{ rawurlencode($metaPixelId) }}&ev=PageView&noscript=1"></noscript>
    @endif
    @include('storefront.partials.header')

    @yield('content')

    @include('storefront.partials.footer')

    <script src="/js/storefront.js?v=20260730-1" defer></script>
    @stack('scripts')
</body>
</html>
