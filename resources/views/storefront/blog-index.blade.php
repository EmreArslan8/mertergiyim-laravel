@php
    use App\Support\Storefront;
    $metaTitle = ($messages['blog']['title'] ?? 'Blog').' | '.$siteName;
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/content.css">
@endpush

@section('content')
    <main class="editorial-page" dir="{{ $dir }}">
        <header class="editorial-list-head">
            <span class="editorial-kicker">{{ $messages['blog']['kicker'] ?? 'MERTER GİYİM' }}</span>
            <h1>{{ $messages['blog']['title'] ?? 'Blog' }}</h1>
        </header>
        <section class="editorial-grid">
            @foreach ($posts as $post)
                <a class="editorial-tile" href="/{{ $locale }}/blog/{{ $post->slug }}">
                    @if ($post->cover_image)
                        <img src="{{ Storefront::storageUrl('site', $post->cover_image) }}" alt="">
                    @endif
                    <div>
                        <time>{{ $post->published_at?->format('d.m.Y') }}</time>
                        <h2>{{ Storefront::text($post->title, $locale) }}</h2>
                        <p>{{ Storefront::text($post->excerpt, $locale) }}</p>
                    </div>
                </a>
            @endforeach
        </section>
    </main>
@endsection
