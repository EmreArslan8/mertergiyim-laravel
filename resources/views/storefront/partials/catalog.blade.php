@php
    use App\Support\Storefront;

    $whatsappNumber = $siteSettings['whatsappNumber'] ?? config('storefront.whatsapp_number');
@endphp

{{-- CategoryBar (CatalogSection.tsx) --}}
@if (($showCategoryFilter ?? true) && count($categories))
    <section class="category-section">
        <div class="category-section-inner">
            <h2>{{ $messages['home']['categories'] ?? '' }}</h2>
            <div class="category-pills" data-category-pills>
                <a class="selected" href="{{ $allCategoriesHref ?? '/'.$locale.'#urunler' }}">{{ $messages['home']['allCategories'] ?? '' }}</a>
                @foreach ($categories as $category)
                    <a href="/{{ $locale }}/kategori/{{ $category->slug }}">{{ Storefront::text($category->name_i18n, $locale) ?: $category->name }}</a>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section class="products" id="urunler">
    <div class="product-grid" data-product-grid>
        @foreach ($cards as $card)
            @php
                $product = $card['product'];
                $href = Storefront::productHref($locale, $product->slug);
                $name = Storefront::text($product->name, $locale);
                $whatsappText = str_replace(
                    '{product}',
                    $product->code.' '.$name,
                    $messages['home']['whatsappText'] ?? '',
                );
                $whatsappHref = 'https://wa.me/'.$whatsappNumber.'?text='.rawurlencode($whatsappText);
            @endphp
            <article class="product-card" data-category-id="{{ $product->category_id }}">
                {{-- ToggleImage.tsx --}}
                <div class="product-image toggle-img" data-toggle-image>
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
                    <div class="product-card-head">
                        <h3>{{ $messages['product']['code'] ?? '' }} {{ $product->code }}</h3>
                        <a class="detail-button mobil-urun-detay-gizle" href="{{ $href }}">{{ $messages['home']['detail'] ?? '' }}</a>
                    </div>
                    <div class="mobil-kategori-goster">{{ Storefront::text($product->category?->name_i18n, $locale) ?: $product->category?->name }}</div>
                    <p class="product-description">{{ Storefront::text($product->description, $locale) }}</p>
                    <h4>{{ $name }}</h4>
                    <div class="product-divider"></div>
                    <strong class="price">{{ Storefront::formatPrice($card['price'], $card['currency']) }}</strong>
                    <a class="buy-button" href="{{ $href }}">{{ $messages['home']['buy'] ?? '' }}</a>
                    <a class="whatsapp-button" href="{{ $whatsappHref }}" target="_blank" rel="noreferrer">{{ $messages['home']['whatsapp'] ?? '' }}</a>
                </div>
            </article>
        @endforeach
    </div>
    <p class="store-empty" data-store-empty @if (count($cards)) style="display:none" @endif>{{ $messages['home']['empty'] ?? '' }}</p>
</section>
