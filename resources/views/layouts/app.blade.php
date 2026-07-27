@php
    use App\Support\Storefront;

    $siteUrl = rtrim(config('storefront.site_url'), '/');
    $canonicalPath = $canonicalPath ?? '/'.$locale;
    $alternatePath = $alternatePath ?? fn (string $code) => '/'.$code;
    $metaTitle = $metaTitle ?? ($messages['meta']['title'] ?? '');
    $metaDescription = $metaDescription ?? ($messages['meta']['description'] ?? '');
    $metaKeywords = $metaKeywords ?? ($messages['meta']['keywords'] ?? '');
    $ogImage = $ogImage ?? null;
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
    <link rel="alternate" hreflang="x-default" href="{{ $siteUrl.$alternatePath(config('storefront.default_locale')) }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $messages['common']['brand'] ?? 'Merter Giyim' }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:locale" content="{{ $locale }}">
    <meta property="og:url" content="{{ $siteUrl.$canonicalPath }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">

    <link rel="icon" href="/icon.png">
    <link rel="apple-touch-icon" href="/apple-icon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- italic ekseni bilerek yok: kaynak index.html ile birebir sahte eğim (synthetic oblique) --}}
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;800;900&display=swap" rel="stylesheet">

    {{-- Kaynak projedeki import sırası korunuyor. --}}
    <link rel="stylesheet" href="/css/storefront.css">
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/hero-override.css">
    <link rel="stylesheet" href="/css/category-filter.css">
    <link rel="stylesheet" href="/css/site-links.css">
    @stack('styles')
</head>
<body>
    @include('storefront.partials.header')

    @yield('content')

    @include('storefront.partials.footer')

    <script src="/js/storefront.js" defer></script>
    @stack('scripts')
</body>
</html>
