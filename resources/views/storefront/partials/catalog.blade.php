@php
    use App\Support\Storefront;

    $whatsappNumber = $siteSettings['whatsappNumber'] ?? config('storefront.whatsapp_number');
    $whatsappOrderingEnabled = (bool) ($siteSettings['whatsappOrderingEnabled'] ?? true);
    $whatsappMessageTemplate = trim((string) ($footerSettings['whatsappMessage'] ?? ''));
@endphp

{{-- CategoryBar (CatalogSection.tsx) --}}
@if (($showCategoryFilter ?? true) && count($categories))
    <section class="category-section">
        <div class="category-section-inner">
            @if ($showCategoryFilterLabel ?? true)
                <h2>{{ $messages['home']['categories'] ?? '' }}</h2>
            @endif
            <div class="category-pills" data-category-pills>
                <button type="button" class="selected" data-category-pill data-category-pick="">{{ $messages['home']['allCategories'] ?? '' }}</button>
                @foreach ($categories as $category)
                    <button type="button" data-category-pill data-category-pick="{{ $category->id }}">{{ Storefront::text($category->name_i18n, $locale) ?: $category->name }}</button>
                @endforeach
                <div class="category-overflow" data-category-overflow hidden>
                    <button type="button" aria-expanded="false" aria-label="Diğer kategorileri göster" data-category-overflow-trigger>+0</button>
                    <div class="category-overflow-menu" data-category-overflow-menu hidden></div>
                </div>
            </div>
        </div>
    </section>
@endif

<section class="products" id="urunler">
    @if ($showCatalogHeading ?? true)
        <div class="catalog-heading">
            <div>
                <span>{{ $messages['home']['collection'] ?? '' }}</span>
                <h2>{{ $messages['home']['featuredProducts'] ?? ($messages['home']['productTitle'] ?? '') }}</h2>
            </div>
            <p>{{ $messages['home']['orderNotice'] ?? '' }}</p>
        </div>
    @endif
    <div class="product-grid" data-product-grid>
        @foreach ($cards as $card)
            @php
                $product = $card['product'];
                $href = Storefront::productHref($locale, $product->slug);
                $name = Storefront::text($product->name, $locale);
                $whatsappText = str_replace(
                    '{product}',
                    $product->code.' '.$name,
                    $whatsappMessageTemplate ?: ($messages['home']['whatsappText'] ?? ''),
                );
                $whatsappHref = 'https://wa.me/'.$whatsappNumber.'?text='.rawurlencode($whatsappText);
            @endphp
            <article class="product-card" data-category-id="{{ $product->category_id }}">
                {{-- ToggleImage.tsx --}}
                <div class="product-image toggle-img" data-toggle-image>
                    @if ($product->pack_size)
                        <span class="pack-badge">{{ str_replace('{count}', $product->pack_size, $messages['home']['packBadge'] ?? '') }}</span>
                    @endif
                    @if (! empty($messages['home']['touchTip']))
                        <span class="touch-tip">{{ $messages['home']['touchTip'] }}</span>
                    @endif
                    @if ($card['primaryImage'])
                        {{-- İlk satır (masaüstünde 3 kart) ekranda açılışta görünür: lazy olmamalı. --}}
                        <img class="main" src="{{ $card['primaryImage'] }}" alt="{{ $name }}"
                             loading="{{ $loop->index < 3 ? 'eager' : 'lazy' }}"
                             @if ($loop->index < 3) fetchpriority="high" @endif
                             decoding="async" style="object-fit:cover">
                    @endif
                    @if ($card['secondaryImage'])
                        <img class="alt" src="{{ $card['secondaryImage'] }}" alt="" loading="lazy" decoding="async" style="object-fit:cover">
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
        @endforeach
    </div>
    <div class="store-empty" role="status" data-store-empty @if (count($cards)) style="display:none" @endif>
        <span class="store-empty-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"/>
                <path d="m4.5 7.8 7.5 4.3 7.5-4.3M12 12v9"/>
            </svg>
        </span>
        <h3 data-empty-title
            data-default-text="{{ $messages['home']['empty'] ?? '' }}"
            data-filter-text="{{ $messages['home']['filterEmpty'] ?? ($messages['home']['empty'] ?? '') }}">
            {{ $messages['home']['empty'] ?? '' }}
        </h3>
        <p data-empty-description
           data-default-text="{{ $messages['home']['emptyDescription'] ?? '' }}"
           data-filter-text="{{ $messages['home']['filterEmptyDescription'] ?? '' }}">
            {{ $messages['home']['emptyDescription'] ?? '' }}
        </p>
        <button type="button" data-empty-reset data-category-pick="" @if (! count($cards)) hidden @endif>
            {{ $messages['home']['showAllProducts'] ?? ($messages['home']['allCategories'] ?? '') }}
        </button>
    </div>
</section>
