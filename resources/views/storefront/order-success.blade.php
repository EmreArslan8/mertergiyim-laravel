@php
    $metaTitle = ($messages['orderSuccess']['title'] ?? 'Siparişiniz Alındı').' | '.$siteName;
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/commerce.css">
@endpush

@section('content')
    <main class="order-success-page" dir="{{ $dir }}" data-order-success>
        <div class="success-mark" aria-hidden="true">✓</div>
        <span>{{ $messages['orderSuccess']['eyebrow'] ?? 'SİPARİŞ OLUŞTURULDU' }}</span>
        <h1>{{ $messages['orderSuccess']['title'] ?? 'Siparişiniz alındı' }}</h1>
        <p>{{ trim((string) ($footerSettings['orderSuccessText'] ?? ''))
            ?: ($messages['orderSuccess']['text'] ?? 'Siparişiniz mağazaya iletildi. Onay için sizinle telefon üzerinden iletişime geçilecektir.') }}</p>

        <section class="success-codes">
            <div>
                <small>{{ $messages['tracking']['orderNo'] ?? 'Sipariş No' }}</small>
                <strong>{{ $order->order_number }}</strong>
            </div>
            <div>
                <small>{{ $messages['tracking']['trackingCode'] ?? 'Takip Kodu' }}</small>
                <strong>{{ $order->tracking_code }}</strong>
            </div>
        </section>

        <p class="success-warning">{{ $messages['orderSuccess']['saveCode'] ?? 'Takip kodunuzu kaydedin. Sipariş durumunu bu kodla sorgulayabilirsiniz.' }}</p>
        <div class="success-actions">
            <a class="primary" href="/{{ $locale }}/siparis-takibi?q={{ urlencode($order->tracking_code) }}">{{ $messages['orderSuccess']['track'] ?? 'Siparişi Takip Et' }}</a>
            <a href="/{{ $locale }}">{{ $messages['common']['home'] ?? 'Anasayfa' }}</a>
        </div>
    </main>
@endsection
