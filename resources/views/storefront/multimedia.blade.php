@php
    use App\Support\Storefront;
    $metaTitle = ($messages['media']['title'] ?? 'Multimedya').' | '.$siteName;
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/commerce.css">
@endpush

@section('content')
    <main class="media-page" dir="{{ $dir }}">
        <header class="commerce-head">
            <span>{{ $messages['media']['eyebrow'] ?? 'KOLEKSİYON HİKÂYELERİ' }}</span>
            <h1>{{ $messages['media']['title'] ?? 'Multimedya' }}</h1>
            <p>{{ $messages['media']['subtitle'] ?? 'Yeni koleksiyonlardan görseller, videolar ve belgeler.' }}</p>
        </header>

        <section class="media-grid">
            @forelse ($mediaItems as $item)
                @php
                    $url = Storefront::storageUrl('site', $item->file_path);
                    $title = Storefront::text($item->title, $locale);
                    $caption = Storefront::text($item->caption, $locale);
                    $alt = Storefront::text($item->alt, $locale) ?: $title;
                @endphp
                <article class="media-card media-card--{{ $item->type }}">
                    @if ($item->type === 'image')
                        <a href="{{ $url }}" target="_blank" rel="noreferrer">
                            <img src="{{ $url }}" alt="{{ $alt }}" loading="lazy">
                        </a>
                    @elseif ($item->type === 'video')
                        <video controls preload="metadata">
                            <source src="{{ $url }}">
                        </video>
                    @else
                        <a class="media-document" href="{{ $url }}" target="_blank" rel="noreferrer">
                            <span>PDF / DOC</span>
                            {{ $messages['media']['openDocument'] ?? 'Belgeyi aç' }}
                        </a>
                    @endif
                    @if ($title || $caption)
                        <div class="media-card-copy">
                            @if ($title)<h2>{{ $title }}</h2>@endif
                            @if ($caption)<p>{{ $caption }}</p>@endif
                        </div>
                    @endif
                </article>
            @empty
                <p class="store-empty">{{ $messages['media']['empty'] ?? 'Henüz multimedya içeriği eklenmedi.' }}</p>
            @endforelse
        </section>
    </main>
@endsection
