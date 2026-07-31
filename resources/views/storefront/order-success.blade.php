@php
    $metaTitle = data_get($messages, 'orderSuccess.title').' | '.$siteName;
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/commerce.css">
@endpush

@section('content')
    <main class="order-success-page" dir="{{ $dir }}" data-order-success>
        <div class="success-mark" aria-hidden="true">✓</div>
        <span>{{ data_get($messages, 'orderSuccess.eyebrow') }}</span>
        <h1>{{ data_get($messages, 'orderSuccess.title') }}</h1>
        <p>{{ trim((string) ($footerSettings['orderSuccessText'] ?? ''))
            ?: data_get($messages, 'orderSuccess.text') }}</p>

        <section class="success-codes">
            <div>
                <small>{{ data_get($messages, 'tracking.orderNo') }}</small>
                <strong>{{ $order->order_number }}</strong>
            </div>
            <div>
                <small>{{ data_get($messages, 'tracking.trackingCode') }}</small>
                <strong>{{ $order->tracking_code }}</strong>
            </div>
        </section>

        <p class="success-warning">{{ data_get($messages, 'orderSuccess.saveCode') }}</p>
        <div class="success-actions">
            <a class="primary" href="/{{ $locale }}/siparis-takibi?q={{ urlencode($order->tracking_code) }}">{{ data_get($messages, 'orderSuccess.track') }}</a>
            <a href="/{{ $locale }}">{{ data_get($messages, 'common.home') }}</a>
        </div>
    </main>
@endsection
