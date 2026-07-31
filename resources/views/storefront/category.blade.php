@php
    use App\Support\Storefront;
    $categoryName = Storefront::text($category->name_i18n, $locale) ?: $category->name;
    $metaTitle = $categoryName.' | '.$siteName;
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/commerce.css?v=20260731-7">
@endpush

@section('content')
    <main class="collection-page" dir="{{ $dir }}">
        <header class="collection-summary">
            <h1>{{ $categoryName }}</h1>
            <p>{{ count($cards) }} {{ data_get($messages, 'category.productCount') }}</p>
        </header>

        @include('storefront.partials.catalog', [
            'showCategoryFilter' => false,
            'showCatalogHeading' => false,
        ])
    </main>
@endsection
