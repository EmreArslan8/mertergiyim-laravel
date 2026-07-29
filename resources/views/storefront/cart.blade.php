@php
    $metaTitle = ($messages['cart']['title'] ?? 'Sepet').' | '.$siteName;
    $bodyClass = 'cart-detail-body';
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/commerce.css">
@endpush

@section('content')
    <main class="cart-page" dir="{{ $dir }}" data-cart-page>
        @if ($errors->any())
            <div class="commerce-alert commerce-alert--error" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="cart-layout">
            <div class="cart-content">
                <header class="cart-heading">
                    <span>SİPARİŞ</span>
                    <h1>{{ $messages['cart']['title'] ?? 'Sepet' }}</h1>
                </header>

                <section class="cart-lines" aria-label="{{ $messages['cart']['title'] ?? 'Sepet' }}">
                    <div class="cart-items" data-cart-items></div>
                    <div class="cart-empty" data-cart-empty>
                        <strong>{{ $messages['cart']['emptyTitle'] ?? 'Sepetiniz boş' }}</strong>
                        <p>{{ $messages['cart']['emptyText'] ?? 'Ürünleri inceleyip renk seçerek sepetinize ekleyebilirsiniz.' }}</p>
                        <a href="/{{ $locale }}#urunler">{{ $messages['cart']['continueShopping'] ?? 'Ürünlere dön' }}</a>
                    </div>
                    <div class="cart-total-row">
                        <span>{{ $messages['cart']['total'] ?? 'Toplam' }}</span>
                        <strong data-cart-total>—</strong>
                    </div>
                </section>
            </div>

            <form class="checkout-card" method="post" action="/{{ $locale }}/siparisler" data-checkout-form>
                @csrf
                <input type="hidden" name="cart" data-cart-payload>
                <input type="hidden" name="order_key" value="{{ old('order_key') ?: (string) \Illuminate\Support\Str::uuid() }}">

                <h2>{{ $messages['cart']['orderInformation'] ?? 'Sipariş Bilgileri' }}</h2>
                <input name="customer_name" required autocomplete="name" value="{{ old('customer_name') }}"
                       aria-label="{{ $messages['order']['name'] ?? 'İsim Soyisim' }}"
                       placeholder="{{ $messages['order']['name'] ?? 'İsim Soyisim' }}">
                <input name="phone" required type="tel" autocomplete="tel" value="{{ old('phone') }}"
                       aria-label="{{ $messages['order']['phone'] ?? 'Telefon' }}"
                       placeholder="{{ $messages['order']['phone'] ?? 'Telefon' }}">
                <textarea name="address" required rows="4"
                          aria-label="{{ $messages['order']['address'] ?? 'Adres' }}"
                          placeholder="{{ $messages['order']['address'] ?? 'Adres' }}">{{ old('address') }}</textarea>
                <textarea name="note" rows="2"
                          aria-label="{{ $messages['order']['note'] ?? 'Not' }}"
                          placeholder="{{ $messages['order']['note'] ?? 'Not' }}">{{ old('note') }}</textarea>

                <button type="submit" data-checkout-submit disabled>
                    {{ $messages['cart']['placeOrder'] ?? 'Sipariş Ver' }}
                </button>
                <a class="bank-accounts-link" href="/{{ $locale }}/banka-hesaplarimiz">
                    {{ $messages['cart']['bankAccounts'] ?? 'Banka Hesaplarımız' }}
                </a>
            </form>
        </div>

        <template data-cart-row-template>
            <article class="cart-line">
                <a class="cart-line-image" data-line-product-link><img alt=""></a>
                <div class="cart-line-copy">
                    <span data-line-code></span>
                    <a class="cart-line-name" data-line-product-link data-line-name></a>
                    <p class="cart-line-package-price">
                        Paket fiyatı: <span data-line-package-price></span>
                        <span data-line-pack-size-wrap> / <span data-line-pack-size></span> adet</span>
                    </p>
                    <div class="cart-line-options">
                        <span>Renk: <b data-line-color></b><i data-line-color-dot></i></span>
                        <span data-line-package-wrap>Paket içeriği: <b data-line-package></b></span>
                    </div>
                </div>
                <div class="cart-line-tools">
                    <button type="button" aria-label="Azalt" data-cart-decrease>−</button>
                    <output data-cart-quantity>1</output>
                    <button type="button" aria-label="Arttır" data-cart-increase>+</button>
                    <button class="cart-remove" type="button" aria-label="{{ $messages['cart']['remove'] ?? 'Ürünü sil' }}"
                            data-cart-remove>
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6"
                                  fill="none" stroke="currentColor" stroke-width="2"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </article>
        </template>
    </main>
@endsection
