@php
    use App\Support\Storefront;
    $title = Storefront::text($page->title, $locale);
    $metaTitle = Storefront::text($page->seo_title, $locale) ?: $title;
    $metaDescription = Storefront::text($page->seo_description, $locale);
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/content.css">
@endpush

@section('content')
    <main class="editorial-page" dir="{{ $dir }}">
        <article class="editorial-card">
            <span class="editorial-kicker">MERTER GİYİM</span>
            <h1>{{ $title }}</h1>
            <div class="editorial-copy">{!! nl2br(e(Storefront::text($page->content, $locale))) !!}</div>
        </article>
    </main>
@endsection
