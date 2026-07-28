@php
    use App\Support\Storefront;

    $metaTitle = $productName.' | Merter Giyim';
    $metaDescription = Storefront::text($product->description, $locale) ?: ($messages['meta']['description'] ?? '');
    $metaKeywords = '';
    $ogImage = $gallery[0] ?? null;
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/product.css">
    <link rel="stylesheet" href="/css/commerce.css">
@endpush

@section('content')
    <main class="detail-page" dir="{{ $dir }}">
        <div class="detail-layout">
            <section class="detail-gallery">
                {{-- ProductGallery.tsx --}}
                <div class="product-image-wrap">
                    <div class="zoom-wrap" data-zoom-wrap>
                        @if (count($gallery))
                            <img class="zoom-main-image" src="{{ $gallery[0] }}" alt="{{ $productName }}" data-zoom-main>
                            <div class="magnifier-lens" aria-hidden="true" data-zoom-lens
                                 style="background-image:url({{ $gallery[0] }});background-position:50% 50%;left:50%;top:50%"></div>
                            <div class="zoom-hint"><span class="zoom-icon">⌕</span> {{ $messages['gallery']['zoomHint'] ?? '' }}</div>
                        @endif
                    </div>
                </div>

                <div class="detail-thumbs" id="thumbs" data-thumbs>
                    @foreach ($gallery as $index => $image)
                        <button type="button"
                                aria-label="{{ $index + 1 }}. {{ $messages['gallery']['showImage'] ?? '' }}"
                                class="detail-thumb {{ $index === 0 ? 'active' : '' }}"
                                data-thumb="{{ $image }}">
                            <img src="{{ $image }}" alt="" width="78" height="60" loading="lazy" style="object-fit:contain">
                        </button>
                    @endforeach
                </div>
                @if (count($gallery))
                    <div class="detail-gallery-note">{{ $messages['gallery']['galleryNote'] ?? '' }}</div>
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

                <div class="recommended-products">
                    <hr>
                    <div class="section-title">{{ $messages['product']['recommended'] ?? '' }}</div>
                    <div class="recommended-grid">
                        @foreach ($recommendations as $item)
                            @php $name = Storefront::text($item['product']->name, $locale); @endphp
                            <a class="recommended-card" href="{{ Storefront::productHref($locale, $item['product']->slug) }}">
                                <div class="recommended-image">
                                    @if ($item['image'])
                                        <img src="{{ $item['image'] }}" alt="{{ $name }}" loading="lazy" style="object-fit:contain">
                                    @endif
                                </div>
                                <div class="recommended-body">
                                    <strong>{{ $name }}</strong>
                                    <span>{{ $item['price'] }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="detail-info">
                <p class="detail-category">{{ $messages['product']['category'] ?? '' }}</p>
                <h1>{{ $productName }}</h1>
                <p class="detail-code">{{ $messages['product']['code'] ?? '' }}: {{ $product->code }}</p>

                {{-- OrderForm.tsx --}}
                <form class="detail-order-form"
                      data-order-form
                      data-product-id="{{ $product->id }}"
                      data-product-slug="{{ $product->slug }}"
                      data-product-name="{{ $productName }}"
                      data-product-code="{{ $product->code }}"
                      data-product-price="{{ $numericPrice }}"
                      data-product-currency="{{ $currencyCode }}"
                      data-product-image="{{ $primaryImage }}"
                      data-order-config="{{ json_encode([
                          'ready' => $messages['cart']['ready'] ?? 'Ürün sepete eklenmeye hazır.',
                          'select' => $messages['cart']['selectVariant'] ?? 'Devam etmek için renk seçin.',
                          'added' => $messages['cart']['added'] ?? 'Ürün sepete eklendi.',
                      ], JSON_UNESCAPED_UNICODE) }}">
                    <div class="detail-price">
                        <div>
                            <span>{{ $messages['product']['price'] ?? '' }}</span>
                            <strong>{{ $price }}</strong>
                        </div>
                        <b>{{ $messages['product']['wholesale'] ?? '' }}</b>
                    </div>
                    {{-- Beden seçimi müşteri isteğiyle vitrinden kaldırıldı; toptan
                         satışta beden asorti gidiyor. Varyant verisi panelde duruyor. --}}
                    <fieldset>
                        <legend>
                            {{ $messages['product']['color'] ?? '' }}
                        </legend>
                        <div class="choice-row color-choices">
                            @foreach ($colors as $color)
                                <button type="button" aria-pressed="false"
                                        data-color-id="{{ $color['id'] }}"
                                        data-color="{{ $color['name'] }}">
                                    <i style="background: {{ $color['hex'] }}"></i>{{ $color['name'] }}
                                </button>
                            @endforeach
                        </div>
                    </fieldset>
                    <div class="quantity-field">
                        <label for="product-quantity">{{ $messages['cart']['quantity'] ?? 'Adet' }}</label>
                        <input id="product-quantity" name="quantity" type="number" min="1" max="99" value="1" inputmode="numeric">
                    </div>
                    <button class="whatsapp-order" type="submit" disabled>{{ $messages['cart']['add'] ?? 'Sepete Ekle' }}</button>
                    <p class="order-note" data-order-note>{{ $messages['cart']['selectVariant'] ?? 'Devam etmek için renk seçin.' }}</p>
                </form>
            </section>
        </div>
    </main>
@endsection
