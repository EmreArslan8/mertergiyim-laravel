@php
    use App\Support\Storefront;

    $brand = $footerSettings['footerBrand'] ?? $siteName;
    $infoTitle = $footerSettings['footerInfoTitle'] ?? 'Bilgilendirmeler';
@endphp
{{-- StoreFooter (StorefrontSections.tsx) --}}
<footer id="iletisim" class="site-footer">
    <div class="site-footer-inner">
        <div class="site-footer-grid">
            <div class="footer-brand-block">
                <a class="footer-logo" href="/{{ $locale }}" aria-label="{{ $brand }}">
                    @if (! empty($siteSettings['siteLogo']))
                        <img class="footer-brand-image" src="{{ Storefront::storageUrl('site', $siteSettings['siteLogo']) }}" alt="{{ $brand }}">
                    @else
                        {{ $brand }}
                    @endif
                </a>
                @if (! empty($footerSettings['footerDescription']))
                    <p>{{ $footerSettings['footerDescription'] }}</p>
                @endif
            </div>
            <div class="footer-info-block">
                <h2>{{ $infoTitle }}</h2>
                <nav class="footer-policy-links" aria-label="{{ $infoTitle }}">
                    @foreach ($footerLinks as $link)
                        @php $external = (bool) preg_match('#^https?://#i', $link->url); @endphp
                        <a href="{{ Storefront::navigationHref($link, $locale) }}"
                           @if ($external) target="_blank" rel="noreferrer" @endif>
                            {{ Storefront::text($link->label, $locale) }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </div>
        <div class="site-footer-bottom">
            @if (! empty($footerSettings['footerAddress']))
                <p>{{ $footerSettings['footerAddress'] }}</p>
            @endif
            @if (! empty($footerSettings['copyright']))
                <small>{{ $footerSettings['copyright'] }}</small>
            @endif
            @if (! empty($siteSettings['contactPhone']) || ! empty($siteSettings['contactEmail']))
                <div class="footer-contact-links">
                    @if (! empty($siteSettings['contactPhone']))
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $siteSettings['contactPhone']) }}">{{ $siteSettings['contactPhone'] }}</a>
                    @endif
                    @if (! empty($siteSettings['contactEmail']))
                        <a href="mailto:{{ $siteSettings['contactEmail'] }}">{{ $siteSettings['contactEmail'] }}</a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</footer>
