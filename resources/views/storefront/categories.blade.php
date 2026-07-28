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
        <header class="collection-hero">
            <span>{{ ($messages['home']['allCategories'] ?? null) ?: 'Tüm Kategoriler' }}</span>
            <h1>{{ $categoriesTitle }}</h1>
            <p>{{ count($cards) }} {{ $messages['category']['productCount'] ?? 'ürün' }}</p>
        </header>

        @include('storefront.partials.catalog', [
            'showCategoryFilter' => true,
            'allCategoriesHref' => '/'.$locale.'/kategori',
        ])
    </main>
@endsection
