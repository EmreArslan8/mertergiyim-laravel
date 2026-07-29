@php
    $contact = $messages['contact'] ?? [];
    $contactTitle = trim((string) ($footerSettings['contactTitle'] ?? ''))
        ?: ($contact['title'] ?? 'İletişim');
    $contactDescription = trim((string) ($footerSettings['contactDescription'] ?? ''))
        ?: ($contact['subtitle'] ?? 'Ürünler ve sipariş süreçleri için bizimle iletişime geçebilirsiniz.');
    $contactAddress = trim((string) ($footerSettings['contactAddress'] ?? ''))
        ?: 'Mehmet Nesih Özmen Mahallesi, Savaş Caddesi, Vardarlı Çarşı, No: 21, Kat: 2, Dükkan: 37, Merter, İstanbul';
    $phone = trim((string) ($siteSettings['contactPhone'] ?? '')) ?: '0532 325 97 88';
    $email = trim((string) ($siteSettings['contactEmail'] ?? '')) ?: 'info@mertertextile.com';
    $whatsappNumber = preg_replace('/\D+/', '', $siteSettings['whatsappNumber'] ?? config('storefront.whatsapp_number'));
    $phoneHref = preg_replace('/[^0-9+]/', '', $phone);
    $instagramUrl = trim((string) ($siteSettings['instagramUrl'] ?? '')) ?: 'https://www.instagram.com/';
    $facebookUrl = trim((string) ($siteSettings['facebookUrl'] ?? '')) ?: 'https://www.facebook.com/';
    $mapIframe = $siteSettings['googleMapsIframe'] ?? '';
    preg_match('/src=["\']([^"\']+)["\']/', $mapIframe, $mapMatch);
    $mapUrl = $mapMatch[1]
        ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d6021.13170382823!2d28.88571669751856!3d41.01287473099048!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14cabb19f605db8f%3A0x924b0806e64dd920!2zVmFyZGFybMSxIMOHYXLFn8SxcSx!5e0!3m2!1str!2str!4v1785262781559!5m2!1str!2str';
    $metaTitle = $contactTitle.' | '.$siteName;
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/commerce.css">
    <link rel="stylesheet" href="/css/contact.css">
@endpush

@section('content')
    <main class="contact-page" dir="{{ $dir }}">
        <div class="contact-heading-wrap">
            @include('storefront.partials.page-heading', [
                'class' => 'page-heading--embedded',
                'eyebrow' => $contact['eyebrow'] ?? 'İLETİŞİM',
                'title' => $contactTitle,
                'description' => $contactDescription,
            ])
        </div>

        <section class="contact-layout" aria-label="{{ $contactTitle }}">
            <div class="contact-map-column">
                <div class="contact-map">
                    <div class="contact-map-label">
                        <span>{{ $contact['map'] ?? 'Harita' }}</span>
                        <strong>{{ $contact['showroom'] ?? $siteName }}</strong>
                    </div>
                    <iframe
                        src="{{ $mapUrl }}"
                        title="{{ $siteName }} Google Maps"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen
                    ></iframe>
                </div>

                <div class="contact-address">
                    <div>
                        <span class="contact-label">{{ $contact['address'] ?? 'Adres' }}</span>
                        <address>{{ $contactAddress }}</address>
                    </div>
                    <a href="{{ $mapUrl }}" target="_blank" rel="noreferrer">
                        <span>{{ $contact['openMap'] ?? 'Haritada aç' }}</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="M5 19 19 5M9 5h10v10" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
            </div>

            <aside class="contact-direct">
                <div class="contact-direct-heading">
                    <span>{{ $contact['directEyebrow'] ?? 'Doğrudan iletişim' }}</span>
                    <h2>{{ $contact['directTitle'] ?? 'Nasıl yardımcı olabiliriz?' }}</h2>
                    <p>{{ $contact['directText'] ?? 'Ürün ve sipariş sorularınız için en hızlı yanıtı WhatsApp üzerinden alabilirsiniz.' }}</p>
                </div>

                <a class="contact-whatsapp" href="https://wa.me/{{ $whatsappNumber }}" target="_blank" rel="noreferrer">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M.1 24 1.8 18A11.8 11.8 0 0 1 .2 12C.2 5.4 5.5.1 12.1.1A11.8 11.8 0 0 1 24 12c0 6.6-5.3 11.9-11.9 11.9-2 0-3.9-.5-5.6-1.4L.1 24Zm6.7-3.7a9.7 9.7 0 0 0 5.2 1.4A9.8 9.8 0 1 0 2.4 12a9.8 9.8 0 0 0 1.5 5.2l-.9 3.3 3.8-1Zm10.8-5.3c-.1-.2-.4-.3-.8-.5l-2.1-1c-.3-.1-.6-.2-.8.2l-.9 1.1c-.2.2-.4.3-.7.1a8 8 0 0 1-2.3-1.4 8.7 8.7 0 0 1-1.6-2c-.2-.3 0-.5.1-.7l.6-.7c.2-.2.2-.4.3-.6l-.1-.6-1-2.3c-.3-.6-.5-.5-.8-.5h-.7c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.9s1.2 3.3 1.4 3.5c.2.2 2.4 3.7 5.8 5.1.8.4 1.5.6 2 .7.8.3 1.6.2 2.2.1.7-.1 2.1-.9 2.4-1.7.3-.9.3-1.6.2-1.8Z"/>
                    </svg>
                    <span>
                        <small>{{ $contact['whatsapp'] ?? 'WhatsApp' }}</small>
                        <strong>{{ $phone }}</strong>
                    </span>
                    <svg class="contact-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path d="m9 18 6-6-6-6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>

                <div class="contact-channels">
                    <a href="tel:{{ $phoneHref }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M6.6 3.8 9 3.2l2.1 5-1.5 1.2c.9 1.8 2.2 3.1 4 4l1.2-1.5 5 2.1-.6 2.4c-.3 1.2-1.4 2-2.6 1.8C10.5 17.3 6.7 13.5 5.8 7.4c-.2-1.2.6-2.3 1.8-2.6Z" stroke-width="1.8" stroke-linejoin="round"/></svg>
                        <div><small>{{ $contact['phone'] ?? 'Telefon' }}</small><strong>{{ $phone }}</strong></div>
                    </a>
                    <a href="mailto:{{ $email }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 6h16v12H4zM4 7l8 6 8-6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <div><small>{{ $contact['email'] ?? 'E-posta' }}</small><strong>{{ $email }}</strong></div>
                    </a>
                </div>

                <div class="contact-social">
                    <span>{{ $contact['social'] ?? 'Sosyal medya' }}</span>
                    <nav aria-label="{{ $contact['social'] ?? 'Sosyal medya' }}">
                        <a href="{{ $instagramUrl }}" target="_blank" rel="noreferrer" aria-label="Instagram">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <rect x="3.5" y="3.5" width="17" height="17" rx="5.2" stroke="currentColor" stroke-width="1.8"/>
                                <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/>
                                <circle cx="17.3" cy="6.9" r="1.1" fill="currentColor"/>
                            </svg>
                            <span>Instagram</span>
                        </a>
                        <a href="{{ $facebookUrl }}" target="_blank" rel="noreferrer" aria-label="Facebook">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M14.2 8.2V6.9c0-.7.5-.9.9-.9h2.1V2.6L14.3 2.5c-3.2 0-4 2.4-4 3.9v1.8H7.8V12h2.5v9.5h3.9V12h2.9l.4-3.8h-3.3Z"/>
                            </svg>
                            <span>Facebook</span>
                        </a>
                    </nav>
                </div>
            </aside>
        </section>

    </main>
@endsection
