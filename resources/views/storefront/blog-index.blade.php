@php
    use App\Support\Storefront;
    $metaTitle = data_get($messages, 'blog.title').' | '.$siteName;
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/content.css?v=20260731-5">
@endpush

@section('content')
    <main class="editorial-page" dir="{{ $dir }}">
        @include('storefront.partials.page-heading', [
            'class' => 'page-heading--embedded page-heading--narrow',
            'eyebrow' => $siteName,
            'title' => data_get($messages, 'blog.title'),
            'description' => $messages['blog']['subtitle'] ?? '',
        ])
        @if (! count($posts))
            <section class="editorial-empty" role="status">
                <span class="editorial-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M5 4h11l3 3v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z"/>
                        <path d="M15 4v4h4M8 12h8M8 16h5"/>
                    </svg>
                </span>
                <h2>{{ data_get($messages, 'blog.empty') }}</h2>
                <p>{{ $messages['blog']['emptyDescription'] ?? '' }}</p>
                <a class="editorial-empty-action" href="/{{ $locale }}">
                    {{ data_get($messages, 'blog.backHome') }}
                </a>
            </section>
        @endif
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
