@php
    $metaTitle = ($messages['contact']['title'] ?? 'İletişim').' | '.$siteName;
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/commerce.css">
@endpush

@section('content')
    <main class="contact-page" dir="{{ $dir }}">
        <section class="contact-intro">
            <span>{{ $messages['contact']['eyebrow'] ?? 'MERTER / İSTANBUL' }}</span>
            <h1>{{ $messages['contact']['title'] ?? 'İletişim' }}</h1>
            <p>{{ $messages['contact']['subtitle'] ?? 'Ürünler, toptan siparişler ve teslimat hakkında bize ulaşın.' }}</p>

            @if (! empty($footerSettings['footerAddress']))
                <address>{{ $footerSettings['footerAddress'] }}</address>
            @endif
            @if (! empty($siteSettings['contactPhone']))
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $siteSettings['contactPhone']) }}">{{ $siteSettings['contactPhone'] }}</a>
            @endif
            @if (! empty($siteSettings['contactEmail']))
                <a href="mailto:{{ $siteSettings['contactEmail'] }}">{{ $siteSettings['contactEmail'] }}</a>
            @endif
            <a href="https://wa.me/{{ $siteSettings['whatsappNumber'] ?? config('storefront.whatsapp_number') }}" target="_blank" rel="noreferrer">WhatsApp</a>
            @if (! empty($siteSettings['instagramUrl']))
                <a href="{{ $siteSettings['instagramUrl'] }}" target="_blank" rel="noreferrer">Instagram</a>
            @endif
        </section>

        <section class="contact-form-card">
            @if (session('contact_success'))
                <div class="commerce-alert commerce-alert--success">{{ $messages['contact']['success'] ?? 'Mesajınız alındı. En kısa sürede sizinle iletişime geçeceğiz.' }}</div>
            @endif
            @if ($errors->any())
                <div class="commerce-alert commerce-alert--error">
                    @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif

            <form method="post" action="/{{ $locale }}/iletisim">
                @csrf
                <label>{{ $messages['order']['name'] ?? 'Ad Soyad' }}<input name="name" required value="{{ old('name') }}"></label>
                <label>{{ $messages['order']['phone'] ?? 'Telefon' }}<input name="phone" required type="tel" value="{{ old('phone') }}"></label>
                <label>{{ $messages['order']['email'] ?? 'E-posta' }} <small>({{ $messages['order']['optional'] ?? 'opsiyonel' }})</small><input name="email" type="email" value="{{ old('email') }}"></label>
                <label>{{ $messages['contact']['subject'] ?? 'Konu' }}<input name="subject" value="{{ old('subject') }}"></label>
                <label>{{ $messages['contact']['message'] ?? 'Mesajınız' }}<textarea name="message" required rows="6">{{ old('message') }}</textarea></label>
                <button type="submit">{{ $messages['contact']['send'] ?? 'Mesajı Gönder' }}</button>
            </form>
        </section>
    </main>
@endsection
