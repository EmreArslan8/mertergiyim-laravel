@php
    $metaTitle = data_get($messages, 'cart.title').' | '.$siteName;
    $bodyClass = 'cart-detail-body';
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/commerce.css?v=20260731-7">
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
                    <span>{{ data_get($messages, 'cart.kicker') }}</span>
                    <h1>{{ data_get($messages, 'cart.title') }}</h1>
                </header>

                <section class="cart-lines" aria-label="{{ data_get($messages, 'cart.title') }}">
                    <div class="cart-items" data-cart-items></div>
                    <div class="cart-empty" data-cart-empty>
                        <strong>{{ data_get($messages, 'cart.emptyTitle') }}</strong>
                        <p>{{ data_get($messages, 'cart.emptyText') }}</p>
                        <a href="/{{ $locale }}#urunler">{{ data_get($messages, 'cart.continueShopping') }}</a>
                    </div>
                    <div class="cart-total-row">
                        <span>{{ data_get($messages, 'cart.total') }}</span>
                        <strong data-cart-total>—</strong>
                    </div>
                </section>
            </div>

            <form class="checkout-card" method="post" action="/{{ $locale }}/siparisler" data-checkout-form>
                @csrf
                <input type="hidden" name="cart" data-cart-payload>
                <input type="hidden" name="order_key" value="{{ old('order_key') ?: (string) \Illuminate\Support\Str::uuid() }}">

                <h2>{{ data_get($messages, 'cart.orderInformation') }}</h2>
                <input name="customer_name" required autocomplete="name" value="{{ old('customer_name') }}"
                       aria-label="{{ data_get($messages, 'order.name') }}"
                       placeholder="{{ data_get($messages, 'order.name') }}">
                <input name="phone" required type="tel" autocomplete="tel" value="{{ old('phone') }}"
                       aria-label="{{ data_get($messages, 'order.phone') }}"
                       placeholder="{{ data_get($messages, 'order.phone') }}">
                <textarea name="address" required rows="4"
                          aria-label="{{ data_get($messages, 'order.address') }}"
                          placeholder="{{ data_get($messages, 'order.address') }}">{{ old('address') }}</textarea>
                <textarea name="note" rows="2"
                          aria-label="{{ data_get($messages, 'order.note') }}"
                          placeholder="{{ data_get($messages, 'order.note') }}">{{ old('note') }}</textarea>

                <button type="submit" data-checkout-submit disabled>
                    {{ data_get($messages, 'cart.placeOrder') }}
                </button>
                @if ($bankAccountsPage)
                    <a class="bank-accounts-link" href="/{{ $locale }}/banka-hesaplarimiz">
                        {{ data_get($messages, 'cart.bankAccounts') }}
                    </a>
                @endif
            </form>
        </div>

        <template data-cart-row-template>
            <article class="cart-line">
                <a class="cart-line-image" data-line-product-link><img alt=""></a>
                <div class="cart-line-copy">
                    <span data-line-code></span>
                    <a class="cart-line-name" data-line-product-link data-line-name></a>
                    <p class="cart-line-package-price">
                        {{ data_get($messages, 'cart.packagePrice') }}: <span data-line-package-price></span>
                        <span data-line-pack-size-wrap> / <span data-line-pack-size></span> {{ data_get($messages, 'common.piece') }}</span>
                    </p>
                    <div class="cart-line-options">
                        <span>{{ data_get($messages, 'cart.color') }}: <b data-line-color></b><i data-line-color-dot></i></span>
                        <span data-line-package-wrap>{{ data_get($messages, 'cart.packageContent') }}: <b data-line-package></b></span>
                    </div>
                </div>
                <div class="cart-line-tools">
                    <button type="button" aria-label="{{ data_get($messages, 'common.decrease') }}" data-cart-decrease>−</button>
                    <output data-cart-quantity>1</output>
                    <button type="button" aria-label="{{ data_get($messages, 'common.increase') }}" data-cart-increase>+</button>
                    <button class="cart-remove" type="button" aria-label="{{ data_get($messages, 'cart.remove') }}"
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
