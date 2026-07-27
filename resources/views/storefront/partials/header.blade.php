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
                $href = $link->link_key === 'tracking'
                    ? '/'.$locale.'/siparis-takibi'
                    : Storefront::href($link->url, $locale);
            @endphp
            <a class="{{ $cart ? 'cart-link' : '' }}" href="{{ $href }}">
                {{ Storefront::text($link->label, $locale) }}
                @if ($cart)
                    @include('storefront.partials.icon', ['name' => 'shopping-cart', 'size' => 20, 'strokeWidth' => 2.2])
                @endif
            </a>
        @endforeach
    </nav>

    @include('storefront.partials.mobile-nav')
</header>
