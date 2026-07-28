@php
    $metaTitle = ($messages['cart']['title'] ?? 'Sepet').' | '.($messages['common']['brand'] ?? 'Merter Giyim');
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/commerce.css">
@endpush

@section('content')
    <main class="cart-page" dir="{{ $dir }}" data-cart-page>
        <header class="commerce-head">
            <span>{{ $messages['common']['brand'] ?? 'Merter Giyim' }}</span>
            <h1>{{ $messages['cart']['title'] ?? 'Sepetiniz' }}</h1>
            <p>{{ $messages['cart']['subtitle'] ?? 'Ürünlerinizi kontrol edin ve sipariş bilgilerinizi tamamlayın.' }}</p>
        </header>

        @if ($errors->any())
            <div class="commerce-alert commerce-alert--error" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="cart-layout">
            <section class="cart-lines" aria-label="{{ $messages['cart']['title'] ?? 'Sepet' }}">
                <div data-cart-items></div>
                <div class="cart-empty" data-cart-empty>
                    <strong>{{ $messages['cart']['emptyTitle'] ?? 'Sepetiniz boş' }}</strong>
                    <p>{{ $messages['cart']['emptyText'] ?? 'Ürünleri inceleyip beden ve renk seçerek sepetinize ekleyebilirsiniz.' }}</p>
                    <a href="/{{ $locale }}#urunler">{{ $messages['cart']['continueShopping'] ?? 'Ürünlere dön' }}</a>
                </div>
            </section>

            <aside class="checkout-card">
                <div class="checkout-total">
                    <span>{{ $messages['cart']['total'] ?? 'Toplam' }}</span>
                    <strong data-cart-total>—</strong>
                </div>

                <form method="post" action="/{{ $locale }}/siparisler" data-checkout-form>
                    @csrf
                    <input type="hidden" name="cart" data-cart-payload>
                    {{-- Çift tıklamada aynı anahtar gelir, ikinci sipariş açılmaz. --}}
                    <input type="hidden" name="order_key" value="{{ old('order_key') ?: (string) \Illuminate\Support\Str::uuid() }}">

                    <label>
                        {{ $messages['order']['name'] ?? 'Ad Soyad' }}
                        <input name="customer_name" required autocomplete="name" value="{{ old('customer_name') }}">
                    </label>
                    <label>
                        {{ $messages['order']['phone'] ?? 'Telefon' }}
                        <input name="phone" required type="tel" autocomplete="tel" value="{{ old('phone') }}" placeholder="05xx xxx xx xx">
                    </label>
                    <label>
                        {{ $messages['order']['address'] ?? 'Adres' }}
                        <textarea name="address" required rows="4">{{ old('address') }}</textarea>
                    </label>
                    <label>
                        {{ $messages['order']['note'] ?? 'Not' }}
                        <textarea name="note" rows="2">{{ old('note') }}</textarea>
                    </label>

                    <button type="submit" data-checkout-submit disabled>
                        {{ $messages['cart']['placeOrder'] ?? 'Siparişi Oluştur' }}
                    </button>
                    <small>{{ $messages['cart']['paymentNote'] ?? 'Online ödeme alınmaz. Siparişiniz mağaza tarafından telefonla teyit edilir.' }}</small>
                </form>
            </aside>
        </div>

        <template data-cart-row-template>
            <article class="cart-line">
                <div class="cart-line-image"><img alt=""></div>
                <div class="cart-line-copy">
                    <span data-line-code></span>
                    <h2 data-line-name></h2>
                    <p data-line-options></p>
                    <button type="button" data-cart-remove>{{ $messages['cart']['remove'] ?? 'Kaldır' }}</button>
                </div>
                <div class="cart-line-tools">
                    <label>
                        {{ $messages['cart']['quantity'] ?? 'Adet' }}
                        <input type="number" min="1" max="99" value="1" data-cart-quantity>
                    </label>
                    <strong data-line-total></strong>
                </div>
            </article>
        </template>
    </main>
@endsection
