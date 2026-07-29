@php
    use App\Support\Storefront;

    $title = trim((string) ($footerSettings['maintenanceTitle'] ?? '')) ?: 'Kısa bir bakımdayız';
    $description = trim((string) ($footerSettings['maintenanceMessage'] ?? ''))
        ?: 'Mağazamızı iyileştiriyoruz. Lütfen kısa süre sonra tekrar ziyaret edin.';
    $logo = ! empty($siteSettings['siteLogo'])
        ? Storefront::storageUrl('site', $siteSettings['siteLogo'])
        : null;
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title }} | {{ $siteName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/maintenance.css">
</head>
<body>
    <main class="maintenance-shell">
        <section class="maintenance-card">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $siteName }}">
            @else
                <strong class="maintenance-brand">{{ $siteName }}</strong>
            @endif
            <span>BAKIM MODU</span>
            <h1>{{ $title }}</h1>
            <p>{{ $description }}</p>
            @if (! empty($siteSettings['contactEmail']))
                <a href="mailto:{{ $siteSettings['contactEmail'] }}">{{ $siteSettings['contactEmail'] }}</a>
            @endif
        </section>
    </main>
</body>
</html>
