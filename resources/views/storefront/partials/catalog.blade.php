@php
    use App\Support\Storefront;

    $whatsappNumber = $siteSettings['whatsappNumber'] ?? config('storefront.whatsapp_number');
    $whatsappMessageTemplate = trim((string) ($footerSettings['whatsappMessage'] ?? ''));
    $homeText = fn (string $key, string $fallback = ''): string => trim((string) ($homeSettings[$key] ?? '')) ?: $fallback;
    $homeRich = fn (string $key, string $fallback = ''): string => Storefront::richText(
        $homeText($key, $fallback),
        $locale,
    );
    $categoryTitle = $homeText('homeCategoryTitle', $messages['home']['categories'] ?? '');
    $allCategoriesLabel = $homeText('homeAllCategoriesLabel', $messages['home']['allCategories'] ?? '');
@endphp

{{-- CategoryBar (CatalogSection.tsx) --}}
@if (($showCategoryFilter ?? true) && count($categories))
    <section class="category-section">
        <div class="category-section-inner">
            @if ($showCategoryFilterLabel ?? true)
                <h2>{{ $categoryTitle }}</h2>
            @endif
            <div class="category-pills" data-category-pills>
                <button type="button" class="selected" data-category-pill data-category-pick="">{{ $allCategoriesLabel }}</button>
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
                <span>{{ $homeText('homeCollectionLabel', $messages['home']['collection'] ?? '') }}</span>
                <h2>{{ $homeText('homeFeaturedTitle', $messages['home']['featuredProducts'] ?? ($messages['home']['productTitle'] ?? '')) }}</h2>
            </div>
            <div class="catalog-description">{!! $homeRich('homeOrderNotice', $messages['home']['orderNotice'] ?? '') !!}</div>
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
            data-default-text="{{ $homeText('homeEmptyTitle', $messages['home']['empty'] ?? '') }}"
            data-filter-text="{{ $homeText('homeFilterEmptyTitle', $messages['home']['filterEmpty'] ?? ($messages['home']['empty'] ?? '')) }}">
            {{ $homeText('homeEmptyTitle', $messages['home']['empty'] ?? '') }}
        </h3>
        <div class="store-empty-description" data-empty-description
             data-default-html="{{ $homeRich('homeEmptyDescription', $messages['home']['emptyDescription'] ?? '') }}"
             data-filter-html="{{ $homeRich('homeFilterEmptyDescription', $messages['home']['filterEmptyDescription'] ?? '') }}">
            {!! $homeRich('homeEmptyDescription', $messages['home']['emptyDescription'] ?? '') !!}
        </div>
        <button type="button" data-empty-reset data-category-pick="" @if (! count($cards)) hidden @endif>
            {{ $homeText('homeShowAllProductsLabel', $messages['home']['showAllProducts'] ?? $allCategoriesLabel) }}
        </button>
    </div>
</section>
