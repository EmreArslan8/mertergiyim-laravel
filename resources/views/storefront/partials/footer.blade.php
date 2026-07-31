@php
    use App\Support\Storefront;

    $brand = $footerSettings['footerBrand'] ?? $siteName;
    $infoTitle = $footerSettings['footerInfoTitle'] ?? data_get($messages, 'common.information');

    // İletişim sütunu — iletişim sayfasıyla aynı kaynaklardan besleniyor.
    $contactTitle = trim((string) ($footerSettings['contactTitle'] ?? ''))
        ?: data_get($messages, 'contact.title');
    $contactAddress = trim((string) ($footerSettings['contactAddress'] ?? $footerSettings['footerAddress'] ?? ''));
    $contactPhone = trim((string) ($siteSettings['contactPhone'] ?? ''));
    $contactEmail = trim((string) ($siteSettings['contactEmail'] ?? ''));
    $whatsappNumber = preg_replace('/\D+/', '', (string) ($siteSettings['whatsappNumber'] ?? config('storefront.whatsapp_number')));
    $hasContact = $contactAddress || $contactPhone || $contactEmail || $whatsappNumber;
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
            @if ($hasContact)
                <div class="footer-info-block footer-contact-block">
                    <h2>{{ $contactTitle }}</h2>
                    <ul class="footer-contact-list">
                        @if ($contactAddress)
                            <li>
                                <span class="footer-contact-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M12 21s7-5.4 7-11a7 7 0 1 0-14 0c0 5.6 7 11 7 11Z"/>
                                        <circle cx="12" cy="10" r="2.6"/>
                                    </svg>
                                </span>
                                <span>{{ $contactAddress }}</span>
                            </li>
                        @endif
                        @if ($contactPhone)
                            <li>
                                <span class="footer-contact-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M6.2 3.5h3l1.5 3.8-2 1.4a12 12 0 0 0 5.6 5.6l1.4-2 3.8 1.5v3a1.8 1.8 0 0 1-2 1.8A15.5 15.5 0 0 1 4.4 5.5a1.8 1.8 0 0 1 1.8-2Z"/>
                                    </svg>
                                </span>
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contactPhone) }}">{{ $contactPhone }}</a>
                            </li>
                        @endif
                        @if ($whatsappNumber)
                            <li>
                                <span class="footer-contact-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M3.8 20.2 5 16.5a7.7 7.7 0 1 1 2.9 2.8l-4.1 0.9Z"/>
                                        <path d="M9.2 8.6c.4 2.4 2.2 4.2 4.6 4.6l1-1.3 1.8.8v1.5c-2.9.6-6.7-2.6-7.4-5.6h1.4l.6 1.6-1 .9"/>
                                    </svg>
                                </span>
                                <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" rel="noreferrer">WhatsApp</a>
                            </li>
                        @endif
                        @if ($contactEmail)
                            <li>
                                <span class="footer-contact-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <rect x="3" y="5.5" width="18" height="13" rx="2"/>
                                        <path d="m3.6 7 8.4 5.6L20.4 7"/>
                                    </svg>
                                </span>
                                <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif
        </div>
        <div class="site-footer-bottom">
            @if (! empty($footerSettings['copyright']))
                <small>{{ $footerSettings['copyright'] }}</small>
            @endif
            @php
                $socialLinks = array_filter([
                    'Instagram' => $siteSettings['instagramUrl'] ?? null,
                    'Facebook' => $siteSettings['facebookUrl'] ?? null,
                    'TikTok' => $siteSettings['tiktokUrl'] ?? null,
                    'YouTube' => $siteSettings['youtubeUrl'] ?? null,
                    'LinkedIn' => $siteSettings['linkedinUrl'] ?? null,
                ]);
            @endphp
            @if ($socialLinks)
                <nav class="footer-contact-links" aria-label="Sosyal medya">
                    @foreach ($socialLinks as $label => $url)
                        <a href="{{ $url }}" target="_blank" rel="noreferrer">{{ $label }}</a>
                    @endforeach
                </nav>
            @endif
        </div>
    </div>
</footer>
