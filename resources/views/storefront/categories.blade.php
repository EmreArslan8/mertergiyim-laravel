@php
    $categoriesTitle = data_get($messages, 'home.categories');
    $metaTitle = $categoriesTitle.' | '.$siteName;
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/commerce.css?v=20260731-7">
@endpush

@section('content')
    <main class="collection-page" dir="{{ $dir }}">
        <header class="collection-summary">
            <h1>{{ data_get($messages, 'category.allProducts') }}</h1>
            <p>{{ count($cards) }} {{ data_get($messages, 'category.productCount') }}</p>
        </header>

        @include('storefront.partials.catalog', [
            'showCategoryFilter' => true,
            'showCategoryFilterLabel' => false,
            'showCatalogHeading' => false,
            'allCategoriesHref' => '/'.$locale.'/kategori',
        ])
    </main>
@endsection
