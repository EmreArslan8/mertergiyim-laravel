@php
    use App\Support\Storefront;

    $isHome = request()->getPathInfo() === Storefront::localePath($locale);
    $categoryColumnCount = max(1, (int) ceil(count($categories) / 5));
@endphp
{{-- StoreHeader.tsx --}}
<header class="site-header{{ $isHome ? '' : ' site-header--solid' }}">
    <a class="brand" href="{{ Storefront::localePath($locale) }}" aria-label="{{ $siteName }}">
        @if (! empty($siteSettings['siteLogo']))
            <img class="brand-image" src="{{ Storefront::storageUrl('site', $siteSettings['siteLogo']) }}" alt="{{ $siteName }}">
        @else
            {{ $siteName }}
        @endif
    </a>
    <nav class="main-nav">
        @foreach ($headerLinks as $link)
            @php
                $cart = Storefront::isCartLink($link);
                $href = Storefront::navigationHref($link, $locale);
                $label = Storefront::text($link->label, $locale);
            @endphp
            @if ($link->link_key === 'categories' && count($categories))
                <div class="desktop-category-menu" data-desktop-category-menu>
                    <button type="button" aria-expanded="false" data-desktop-category-trigger>
                        {{ $label }}
                        @include('storefront.partials.icon', ['name' => 'chevron-down', 'size' => 14])
                    </button>
                    <div class="desktop-category-dropdown" data-desktop-category-dropdown
                         style="--category-columns: {{ $categoryColumnCount }}">
                        <a class="desktop-category-all" href="{{ Storefront::localePath($locale, '/kategori') }}">
                            {{ data_get($messages, 'home.allCategories') }}
                        </a>
                        <div class="desktop-category-list">
                            @foreach ($categories as $category)
                                <a href="{{ Storefront::localePath($locale, '/kategori/'.$category->slug) }}">
                                    {{ Storefront::text($category->name_i18n, $locale) ?: $category->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <a class="{{ $cart ? 'cart-link' : '' }}" href="{{ $href }}">
                    @if ($cart)
                        @include('storefront.partials.icon', ['name' => 'shopping-cart', 'size' => 20, 'strokeWidth' => 2])
                    @endif
                    {{ $label }}
                    @if ($cart)
                        <span class="cart-count" data-cart-count hidden>0</span>
                    @endif
                </a>
            @endif
        @endforeach
    </nav>

    @include('storefront.partials.mobile-nav')
</header>
