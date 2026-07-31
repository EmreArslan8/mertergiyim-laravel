@php
    use App\Support\Storefront;

    $whatsappNumber = $siteSettings['whatsappNumber'] ?? config('storefront.whatsapp_number');
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
                    <button type="button" aria-expanded="false" aria-label="{{ data_get($messages, 'common.showMoreCategories') }}" data-category-overflow-trigger>+0</button>
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
            {{-- Kart işaretlemesi ürün detayındaki önerilerle ortak --}}
            <x-storefront.product-card :card="$card" :eager-image="$loop->index < 3" />
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
