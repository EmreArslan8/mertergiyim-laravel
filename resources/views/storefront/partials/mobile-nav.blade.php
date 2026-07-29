@php
    use App\Support\Storefront;

    $activeLanguage = collect($languages)->firstWhere('code', $locale);
@endphp
{{-- MobileNavigation.tsx --}}
<div class="mobile-header-actions" data-mobile-nav data-locale="{{ $locale }}">
    <div class="mobile-language-picker">
        <button type="button" class="mobile-language-trigger" aria-expanded="false" aria-controls="mobile-language-menu" data-language-trigger>
            {{ data_get($activeLanguage, 'name', strtoupper($locale)) }}
            @include('storefront.partials.icon', ['name' => 'chevron-down', 'size' => 18])
        </button>
        <div class="mobile-language-menu" id="mobile-language-menu" data-language-menu style="display:none">
            @foreach ($languages as $language)
                <a class="{{ $language->code === $locale ? 'selected' : '' }}" href="/{{ $language->code }}">{{ $language->name }}</a>
            @endforeach
        </div>
    </div>

    <button type="button" class="mobile-menu-trigger" aria-label="Menu" aria-expanded="false" aria-controls="mobile-main-menu" data-menu-trigger>
        <span data-menu-icon="open">@include('storefront.partials.icon', ['name' => 'menu', 'size' => 38])</span>
        <span data-menu-icon="close" style="display:none">@include('storefront.partials.icon', ['name' => 'x', 'size' => 34])</span>
    </button>

    <nav class="mobile-main-menu" id="mobile-main-menu" data-main-menu style="display:none">
        @foreach ($headerLinks as $link)
            @php $label = Storefront::text($link->label, $locale); @endphp

            @if ($link->link_key === 'categories' && count($categories))
                <div class="mobile-category-group">
                    <button type="button" class="mobile-category-toggle" aria-expanded="false" aria-controls="mobile-category-list" data-category-toggle>
                        {{ $label }}
                        @include('storefront.partials.icon', ['name' => 'chevron-down', 'size' => 18])
                    </button>
                    <div class="mobile-category-list" id="mobile-category-list" data-category-list style="display:none">
                        <button type="button" class="mobile-category-back" data-category-back>
                            @include('storefront.partials.icon', ['name' => 'chevron-left', 'size' => 20])
                            {{ $label }}
                        </button>
                        <a class="selected" href="/{{ $locale }}/kategori">{{ ($messages['home']['allCategories'] ?? null) ?: 'Tümü' }}</a>
                        @foreach ($categories as $category)
                            <a href="/{{ $locale }}/kategori/{{ $category->slug }}">{{ Storefront::text($category->name_i18n, $locale) ?: $category->name }}</a>
                        @endforeach
                    </div>
                </div>
            @else
                @php
                    $cart = Storefront::isCartLink($link);
                    $href = Storefront::navigationHref($link, $locale);
                @endphp
                <a href="{{ $href }}">
                    {{ $label }}
                    @if ($cart)
                        @include('storefront.partials.icon', ['name' => 'shopping-cart', 'size' => 19])
                        <span class="cart-count" data-cart-count hidden>0</span>
                    @endif
                </a>
            @endif
        @endforeach
    </nav>
</div>
