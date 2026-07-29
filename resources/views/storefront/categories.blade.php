@php
    $categoriesTitle = ($messages['home']['categories'] ?? null) ?: 'Kategoriler';
    $metaTitle = $categoriesTitle.' | '.$siteName;
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/commerce.css">
@endpush

@section('content')
    <main class="collection-page" dir="{{ $dir }}">
        <header class="collection-summary">
            <h1>{{ $messages['category']['allProducts'] ?? 'Tüm Ürünler' }}</h1>
            <p>{{ count($cards) }} {{ $messages['category']['productCount'] ?? 'ürün' }}</p>
        </header>

        @include('storefront.partials.catalog', [
            'showCategoryFilter' => true,
            'showCategoryFilterLabel' => false,
            'showCatalogHeading' => false,
            'allCategoriesHref' => '/'.$locale.'/kategori',
        ])
    </main>
@endsection
