@php
    use App\Support\Storefront;

    $metaTitle = $productName.' | '.$siteName;
    $metaDescription = Storefront::plainText($product->description, $locale) ?: ($messages['meta']['description'] ?? '');
    $metaKeywords = '';
    $ogImage = $gallery[0] ?? null;
    $packageTotal = Storefront::formatPrice($numericPrice * $packSize, $currencyDisplay);
    $bodyClass = 'product-detail-body';

    $packageContent = collect($packageBreakdown)
        ->map(fn ($item) => $item['name'].': '.$item['quantity'])
        ->join(' / ');
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/product.css?v=20260730-1">
    <link rel="stylesheet" href="/css/commerce.css">
@endpush

@section('content')
    <main class="detail-page" dir="{{ $dir }}">
        <div class="detail-layout">
            <section class="detail-gallery">
                <div class="product-image-wrap">
                    <div class="zoom-wrap" data-zoom-wrap>
                        @if (count($gallery))
                            <img class="zoom-main-image" src="{{ $gallery[0] }}" alt="{{ $galleryAlts[0] ?? $productName }}"
                                 fetchpriority="high" data-zoom-main>
                            <div class="magnifier-lens" aria-hidden="true" data-zoom-lens
                                 style="background-image:url({{ $gallery[0] }});background-position:50% 50%;left:50%;top:50%"></div>
                            <div class="zoom-hint">
                                <span class="zoom-icon" aria-hidden="true">⌕</span>
                                {{ $messages['gallery']['zoomHint'] ?? '' }}
                            </div>
                        @else
                            <div class="detail-image-empty">{{ $productName }}</div>
                        @endif
                    </div>
                </div>

                @if (count($gallery) > 1)
                    <div class="detail-thumbs" id="thumbs" data-thumbs>
                        @foreach ($gallery as $index => $image)
                            <button type="button"
                                    aria-label="{{ $index + 1 }}. {{ $galleryAlts[$index] ?? $productName }}"
                                    class="detail-thumb {{ $index === 0 ? 'active' : '' }}"
                                    data-thumb="{{ $image }}"
                                    data-thumb-alt="{{ $galleryAlts[$index] ?? $productName }}">
                                <img src="{{ $image }}" alt="" width="96" height="112" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif

                @if ($product->video_url)
                    <div class="detail-video">
                        <hr>
                        <div class="section-title">Video</div>
                        @if ($videoEmbedUrl)
                            <div class="detail-video-frame">
                                <iframe src="{{ $videoEmbedUrl }}" title="{{ $productName }}" loading="lazy"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen></iframe>
                            </div>
                        @else
                            <video class="detail-video-frame" src="{{ $product->video_url }}" controls playsinline></video>
                        @endif
                    </div>
                @endif

            </section>

            <section class="detail-info">
                <h1>{{ $productName }}</h1>
                <div class="detail-meta">
                    <span>{{ $categoryName ?: ($messages['product']['category'] ?? '') }}</span>
                    <b>{{ $product->code }}</b>
                </div>

                <div class="detail-pricing">
                    <span>{{ $packSize > 1 ? data_get($messages, 'product.unitPrice') : data_get($messages, 'product.price') }}</span>
                    <strong>{{ $price }}</strong>
                    @if ($packSize > 1)
                        <p>{{ strtr(data_get($messages, 'product.packageSummary'), [
                            '{total}' => $packageTotal,
                            '{count}' => $packSize,
                        ]) }}</p>
                    @endif
                </div>

                @if ($productDescription)
                    <div class="detail-description">
                        <span>{{ data_get($messages, 'product.descriptionTitle') }}</span>
                        {{-- İçerik App\Support\Storefront::richText() ile temizlendi. --}}
                        <div class="rich-text">{!! $productDescription !!}</div>
                    </div>
                @endif

                <form class="detail-order-form"
                      data-order-form
                      data-product-id="{{ $product->id }}"
                      data-product-slug="{{ $product->slug }}"
                      data-product-name="{{ $productName }}"
                      data-product-code="{{ $product->code }}"
                      data-product-price="{{ $numericPrice * $packSize }}"
                      data-product-currency="{{ $currencyCode }}"
                      data-product-image="{{ $primaryImage }}"
                      data-product-pack-size="{{ $packSize }}"
                      data-product-package-content="{{ $packageContent }}"
                      data-product-package-content-source="database"
                      data-order-config="{{ json_encode([
                          'ready' => data_get($messages, 'cart.ready'),
                          'select' => data_get($messages, 'cart.selectVariant'),
                          'added' => data_get($messages, 'cart.added'),
                          'goToCart' => data_get($messages, 'cart.goToCart'),
                          'cartHref' => '/'.$locale.'/sepet',
                      ], JSON_UNESCAPED_UNICODE) }}">
                    <fieldset>
                        <legend>{{ data_get($messages, 'product.color') }}</legend>
                        <div class="choice-row color-choices">
                            @foreach ($colors as $color)
                                <button type="button"
                                        class="{{ count($colors) === 1 ? 'selected' : '' }}"
                                        aria-pressed="{{ count($colors) === 1 ? 'true' : 'false' }}"
                                        data-color-id="{{ $color['id'] }}"
                                        data-color="{{ $color['name'] }}"
                                        data-color-hex="{{ $color['hex'] }}">
                                    <span class="choice-label">
                                        <i style="background: {{ $color['hex'] }}"></i>
                                        <span>{{ $color['name'] }}</span>
                                    </span>
                                    <span class="choice-check" aria-hidden="true">✓</span>
                                </button>
                            @endforeach
                        </div>
                    </fieldset>

                    @if ($packSize > 1 || count($sizes))
                        <div class="detail-package">
                            <div class="detail-section-heading">
                                <span>{{ data_get($messages, 'product.packageContent') }}</span>
                                @if ($packSize > 1)
                                    <b>{{ $packSize }} {{ data_get($messages, 'common.piece') }}</b>
                                @endif
                            </div>
                            @if (count($packageBreakdown))
                                <div class="detail-size-list" aria-label="{{ data_get($messages, 'product.packageSizes') }}">
                                    @foreach ($packageBreakdown as $item)
                                        <span>
                                            <strong>{{ $item['name'] }}</strong>
                                            <small>{{ $item['quantity'] }} {{ data_get($messages, 'common.piece') }}</small>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="detail-quantity-row">
                        <span>{{ data_get($messages, 'cart.quantity') }}</span>
                        <div class="detail-stepper">
                            <button type="button" aria-label="{{ data_get($messages, 'common.decrease') }}" data-quantity-decrease>−</button>
                            <output data-quantity-value>1</output>
                            <button type="button" aria-label="{{ data_get($messages, 'common.increase') }}" data-quantity-increase>+</button>
                        </div>
                    </div>
                    <input name="quantity" type="hidden" value="1">
                    <button class="whatsapp-order" type="submit" data-order-submit
                            @if (count($colors) > 1) disabled @endif>
                        <span>{{ data_get($messages, 'cart.add') }}</span>
                    </button>
                    <p class="order-note" data-order-note>
                        {{ count($colors) <= 1
                            ? data_get($messages, 'cart.ready')
                            : data_get($messages, 'cart.selectVariant') }}
                    </p>

                    <div class="detail-mobile-purchase">
                        <div class="detail-mobile-purchase-inner">
                            <div class="detail-mobile-purchase-grid">
                                <div class="detail-mobile-add-cluster">
                                    <div class="detail-mobile-wheel">
                                        <button type="button" aria-label="{{ data_get($messages, 'common.decrease') }}" data-quantity-decrease>
                                            <span data-quantity-previous></span>
                                        </button>
                                        <output data-quantity-value>1</output>
                                        <button type="button" aria-label="{{ data_get($messages, 'common.increase') }}" data-quantity-increase>
                                            <span data-quantity-next>2</span>
                                        </button>
                                    </div>
                                    <button class="detail-mobile-add" type="submit" data-order-submit
                                            @if (count($colors) > 1) disabled @endif>
                                        {{ data_get($messages, 'cart.add') }}
                                    </button>
                                </div>
                                <a class="detail-mobile-cart" href="/{{ $locale }}/sepet"
                                   aria-label="{{ data_get($messages, 'cart.title') }}">
                                    @include('storefront.partials.icon', ['name' => 'shopping-cart', 'size' => 28, 'strokeWidth' => 2.4])
                                    <span class="cart-count" data-cart-count hidden>0</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </section>
        </div>

        @if (count($recommendations))
            <section class="recommended-products">
                <div class="recommended-heading">
                    <span>{{ data_get($messages, 'product.recommendedKicker') }}</span>
                    <h2>{{ data_get($messages, 'product.recommended') }}</h2>
                </div>
                {{-- Kartlar ana sayfadakiyle aynı bileşen --}}
                <div class="product-grid">
                    @foreach ($recommendations as $card)
                        <x-storefront.product-card :card="$card" />
                    @endforeach
                </div>
            </section>
        @endif
    </main>
@endsection
