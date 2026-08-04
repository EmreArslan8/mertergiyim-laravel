@php
    use App\Support\Storefront;

    $metaTitle = data_get($messages, 'media.title').' | '.$siteName;
    $mediaUi = [
        'close' => data_get($messages, 'media.close'),
        'previous' => data_get($messages, 'media.previous'),
        'next' => data_get($messages, 'media.next'),
        'open' => data_get($messages, 'media.open'),
        'openDocument' => data_get($messages, 'media.openDocument'),
        'collection' => data_get($messages, 'media.collection'),
    ];
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/commerce.css?v=20260802-1">
@endpush

@section('content')
    <main class="media-page" dir="{{ $dir }}">
        @include('storefront.partials.page-heading', [
            'eyebrow' => data_get($messages, 'media.eyebrow'),
            'title' => data_get($messages, 'media.title'),
        ])

        <section class="media-gallery-section" aria-label="{{ data_get($messages, 'media.title') }}">
            @forelse ($mediaPosts as $post)
                @php
                    $cover = $post->files->first();
                    $postTitle = Storefront::text($post->title, $locale) ?: data_get($messages, 'media.untitled');
                    // Açıklama panelde zengin editörle girilir; görüntüleyiciye temizlenmiş HTML gider.
                    $postDescription = Storefront::richText($post->description, $locale);
                    $postData = [
                        'title' => $postTitle,
                        'description' => $postDescription,
                        'files' => $post->files->map(function ($file) use ($locale, $postTitle) {
                            return [
                                'type' => $file->type,
                                'url' => Storefront::storageUrl('site', $file->file_path),
                                'alt' => Storefront::text($file->alt, $locale) ?: $postTitle,
                            ];
                        })->values()->all(),
                    ];
                @endphp

                <button
                    class="media-tile"
                    type="button"
                    data-media-post
                    aria-label="{{ $postTitle }} — {{ $mediaUi['open'] }}"
                >
                    @if ($cover->type === 'image')
                        <img
                            src="{{ Storefront::storageUrl('site', $cover->file_path) }}"
                            alt="{{ Storefront::text($cover->alt, $locale) ?: $postTitle }}"
                            loading="lazy"
                        >
                    @elseif ($cover->type === 'video')
                        <video
                            src="{{ Storefront::storageUrl('site', $cover->file_path) }}"
                            muted
                            playsinline
                            preload="metadata"
                            aria-label="{{ $postTitle }}"
                        ></video>
                    @else
                        <span class="media-tile-document" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M7 3h7l5 5v13H7zM14 3v6h5M10 14h6M10 18h4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            PDF
                        </span>
                    @endif

                    <span class="media-tile-shade" aria-hidden="true"></span>
                    <span class="media-tile-copy">
                        <span>{{ $postTitle }}</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>

                    @if ($post->files->count() > 1)
                        <span class="media-tile-count" aria-label="{{ strtr(data_get($messages, 'media.fileCount'), ['{count}' => $post->files->count()]) }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <rect x="6" y="6" width="13" height="13" rx="1.5" stroke-width="1.7"/>
                                <path d="M16 6V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h1" stroke-width="1.7"/>
                            </svg>
                            {{ $post->files->count() }}
                        </span>
                    @elseif ($cover->type === 'video')
                        <span class="media-tile-count media-tile-count--icon" aria-label="Video">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="m9 7 8 5-8 5z"/>
                            </svg>
                        </span>
                    @endif

                    <script type="application/json" data-media-post-data>
                        @json($postData)
                    </script>
                </button>
            @empty
                <div class="media-empty">
                    <span class="media-empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="3" y="5" width="18" height="14" rx="2" stroke-width="1.5"/>
                            <circle cx="9" cy="10" r="1.5" stroke-width="1.5"/>
                            <path d="m5.5 17 4.5-4 3 2.5 2.5-2 3 3.5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <strong>{{ data_get($messages, 'media.emptyTitle') }}</strong>
                    <p>{{ data_get($messages, 'media.empty') }}</p>
                </div>
            @endforelse
        </section>

        @if ($mediaPosts->isNotEmpty())
            <div
                class="media-viewer"
                data-media-viewer
                data-label-close="{{ $mediaUi['close'] }}"
                data-label-previous="{{ $mediaUi['previous'] }}"
                data-label-next="{{ $mediaUi['next'] }}"
                data-label-open-document="{{ $mediaUi['openDocument'] }}"
                hidden
                role="dialog"
                aria-modal="true"
                aria-labelledby="media-viewer-title"
            >
                <button class="media-viewer-backdrop" type="button" data-media-close tabindex="-1" aria-label="{{ $mediaUi['close'] }}"></button>

                <article class="media-viewer-panel">
                    <div class="media-viewer-stage">
                        <div class="media-viewer-asset" data-media-asset></div>

                        <div class="media-viewer-topline">
                            <span data-media-counter></span>
                            <button class="media-viewer-close" type="button" data-media-close aria-label="{{ $mediaUi['close'] }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path d="m6 6 12 12M18 6 6 18" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </div>

                        <button class="media-viewer-arrow media-viewer-arrow--previous" type="button" data-media-previous aria-label="{{ $mediaUi['previous'] }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path d="m15 18-6-6 6-6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <button class="media-viewer-arrow media-viewer-arrow--next" type="button" data-media-next aria-label="{{ $mediaUi['next'] }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path d="m9 18 6-6-6-6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>

                    <aside class="media-viewer-details">
                        <div>
                            <span class="media-viewer-eyebrow">{{ $mediaUi['collection'] }}</span>
                            <h2 id="media-viewer-title" data-media-title></h2>
                            <div class="media-viewer-description" data-media-description></div>
                        </div>
                        <div class="media-viewer-thumbnails" data-media-thumbnails></div>
                    </aside>
                </article>
            </div>
        @endif
    </main>
@endsection
