@php
    use App\Support\Storefront;
    $categoryName = Storefront::text($category->name_i18n, $locale) ?: $category->name;
    $metaTitle = $categoryName.' | '.$siteName;
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/commerce.css">
@endpush

@section('content')
    <main class="collection-page" dir="{{ $dir }}">
        <header class="collection-hero">
            <span>{{ $messages['home']['categories'] ?? 'Kategoriler' }}</span>
            <h1>{{ $categoryName }}</h1>
            <p>{{ count($cards) }} {{ $messages['category']['productCount'] ?? 'ürün' }}</p>
        </header>

        @include('storefront.partials.catalog', ['showCategoryFilter' => false])
    </main>
@endsection
