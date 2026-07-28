@php
    use App\Support\Storefront;

    $isHome = request()->path() === $locale;
@endphp
{{-- StoreHeader.tsx --}}
<header class="site-header{{ $isHome ? '' : ' site-header--solid' }}">
    <a class="brand" href="/{{ $locale }}">{{ $messages['common']['brand'] ?? '' }}</a>
    <nav class="main-nav">
        @foreach ($headerLinks as $link)
            @php
                $cart = Storefront::isCartLink($link);
                $href = Storefront::navigationHref($link, $locale);
            @endphp
            <a class="{{ $cart ? 'cart-link' : '' }}" href="{{ $href }}">
                {{ Storefront::text($link->label, $locale) }}
                @if ($cart)
                    @include('storefront.partials.icon', ['name' => 'shopping-cart', 'size' => 20, 'strokeWidth' => 2.2])
                    <span class="cart-count" data-cart-count hidden>0</span>
                @endif
            </a>
        @endforeach
    </nav>

    @include('storefront.partials.mobile-nav')
</header>
