@php
    use App\Support\Storefront;
    $title = Storefront::text($post->title, $locale);
    $metaTitle = $title.' | '.$siteName;
    $metaDescription = Storefront::text($post->excerpt, $locale);
    $ogImage = $post->cover_image ? Storefront::storageUrl('site', $post->cover_image) : null;
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/content.css">
@endpush

@section('content')
    <main class="editorial-page" dir="{{ $dir }}">
        <article class="editorial-card">
            <span class="editorial-kicker">{{ $post->published_at?->format('d.m.Y') }}</span>
            <h1>{{ $title }}</h1>
            @if ($ogImage)
                <img class="editorial-cover" src="{{ $ogImage }}" alt="{{ $title }}">
            @endif
            <div class="editorial-copy">{!! nl2br(e(Storefront::text($post->content, $locale))) !!}</div>
        </article>
    </main>
@endsection
