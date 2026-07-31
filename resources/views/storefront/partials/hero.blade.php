@php
    use App\Support\Storefront;

    $heroSiteHost = preg_replace('/^www\./i', '', (string) (parse_url(
        (string) ($siteSettings['siteUrl'] ?? config('storefront.site_url')),
        PHP_URL_HOST,
    ) ?: $siteName));
@endphp
{{-- StoreHero (StorefrontSections.tsx) --}}
<section class="hero">
    <div class="hero-media" style="{{ $heroImage ? 'background-image:url('.$heroImage.')' : 'background-image:none;background-color:#c4c4c4' }}"></div>
    <div class="hero-overlay"></div>
    <div class="hero-copy">
        @if ($hero && $hero->title)
            <p class="hero-eyebrow">{{ $heroSiteHost }}</p>
            <h1>{{ Storefront::text($hero->title, $locale) }}</h1>
            @if ($hero->button_url)
                <a href="{{ Storefront::href($hero->button_url, $locale) }}">{{ Storefront::text($hero->button_text, $locale) }}</a>
            @endif
        @endif
    </div>
</section>
