@props(['card'])

@php
    use App\Support\Storefront;

    /**
     * Ortak ürün kartı: ana sayfa kataloğu ve ürün detayındaki öneriler
     * aynı işaretlemeyi kullanır. $card, ProductCardService::make() çıktısının
     * bir öğesidir (product, images, price, currency).
     *
     * WhatsApp ayarları ve $messages/$siteSettings global paylaşımdan gelir
     * (SetStorefrontLocale), o yüzden bileşen ek parametre istemez.
     */
    $product = $card['product'];
    $href = Storefront::productHref($locale, $product->slug);
    $name = Storefront::text($product->name, $locale);
    $images = $card['images'] ?? [];

    $whatsappNumber = $siteSettings['whatsappNumber'] ?? config('storefront.whatsapp_number');
    $whatsappOrderingEnabled = (bool) ($siteSettings['whatsappOrderingEnabled'] ?? true);
    $whatsappMessageTemplate = trim((string) ($footerSettings['whatsappMessage'] ?? ''));
    $whatsappText = str_replace(
        '{product}',
        $product->code.' '.$name,
        $whatsappMessageTemplate ?: ($messages['home']['whatsappText'] ?? ''),
    );
    $whatsappHref = 'https://wa.me/'.$whatsappNumber.'?text='.rawurlencode($whatsappText);

    // Ana sayfada ilk üç kartın ilk görseli öncelikli yüklenir; öneri
    // listesinde kartlar ekranın altında olduğu için hepsi lazy kalır.
    $eager = ($eagerImage ?? false) === true;
@endphp

<article class="product-card" data-category-id="{{ $product->category_id }}">
    <a class="product-card-link" href="{{ $href }}" aria-label="{{ $name }} ürün detayını aç"></a>
    <div class="product-image">
        @if ($product->pack_size)
            <span class="pack-badge">{{ str_replace('{count}', $product->pack_size, $messages['home']['packBadge'] ?? '') }}</span>
        @endif
        @if (count($images) > 1 && ! empty($messages['home']['touchTip']))
            <span class="touch-tip">{{ $messages['home']['touchTip'] }}</span>
        @endif
        @if (count($images))
            <div class="product-card-gallery" data-card-gallery>
                @foreach ($images as $image)
                    <a class="product-card-slide" href="{{ $href }}" aria-label="{{ $name }} · {{ $loop->iteration }}. görsel">
                        <img
                             @if ($loop->first) src="{{ $image }}" @else data-src="{{ $image }}" @endif
                             alt="{{ $loop->first ? $name : '' }}"
                             loading="{{ $eager && $loop->first ? 'eager' : 'lazy' }}"
                             @if ($eager && $loop->first) fetchpriority="high" @endif
                             decoding="async">
                    </a>
                @endforeach
            </div>
            @if (count($images) > 1)
                <div class="product-card-dots" data-card-dots aria-hidden="true">
                    @foreach ($images as $image)
                        <i class="{{ $loop->first ? 'selected' : '' }}"></i>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
    <div class="product-card-body">
        {{-- Referans: KOD ve fiyat aynı satırda, taban hizasında --}}
        <div class="product-card-head">
            <h3>{{ $messages['product']['code'] ?? '' }} {{ $product->code }}</h3>
            <strong class="price">{{ Storefront::formatPrice($card['price'], $card['currency']) }}</strong>
        </div>
        <div class="product-category">{{ Storefront::text($product->category?->name_i18n, $locale) ?: $product->category?->name }}</div>
        <h4>{{ $name }}</h4>
        <div class="product-divider"></div>
        <div class="product-actions {{ $whatsappOrderingEnabled ? '' : 'product-actions--single' }}">
            <a class="buy-button" href="{{ $href }}">{{ $messages['cart']['add'] ?? ($messages['home']['buy'] ?? '') }}</a>
            @if ($whatsappOrderingEnabled)
                <a class="whatsapp-button" href="{{ $whatsappHref }}" target="_blank" rel="noreferrer">
                    <img class="whatsapp-icon" src="/images/whatsapp.svg" alt="" aria-hidden="true">
                    <span>{{ $messages['home']['whatsapp'] ?? '' }}</span>
                </a>
            @endif
        </div>
        <a class="detail-link" href="{{ $href }}">{{ $messages['home']['detail'] ?? '' }}</a>
    </div>
</article>
