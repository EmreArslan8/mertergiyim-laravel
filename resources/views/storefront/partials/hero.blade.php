@php
    use App\Support\Storefront;
@endphp
{{-- StoreHero (StorefrontSections.tsx) --}}
<section class="hero" data-hero-slider data-autoplay="6000" aria-roledescription="carousel">
    <div class="hero-slides">
        @forelse ($heroSlides as $index => $item)
            @php($slide = $item['record'])
            <article class="hero-slide {{ $index === 0 ? 'is-active' : '' }}"
                     data-hero-slide aria-hidden="{{ $index === 0 ? 'false' : 'true' }}">
                <div class="hero-media" style="background-image:url({{ $item['image'] }})"></div>
                <div class="hero-overlay"></div>
                <div class="hero-copy">
                    @if ($slide->title)
                        @php($eyebrow = Storefront::text($slide->eyebrow ?? [], $locale))
                        @if ($eyebrow !== '')
                            <p class="hero-eyebrow">{{ $eyebrow }}</p>
                        @endif
                        <h1 class="hero-title">{!! Storefront::richText($slide->title, $locale) !!}</h1>
                        @if ($slide->button_url)
                            <a href="{{ Storefront::href($slide->button_url, $locale) }}">{{ Storefront::text($slide->button_text, $locale) }}</a>
                        @endif
                    @endif
                </div>
            </article>
        @empty
            <div class="hero-slide is-active" data-hero-slide aria-hidden="false">
                <div class="hero-media" style="background-image:none;background-color:#c4c4c4"></div>
                <div class="hero-overlay"></div>
            </div>
        @endforelse
    </div>

    @if (count($heroSlides) > 1)
        <button class="hero-nav hero-nav--previous" type="button"
                aria-label="{{ data_get($messages, 'media.previous') }}" data-hero-previous>
            <span aria-hidden="true">{{ $dir === 'rtl' ? '›' : '‹' }}</span>
        </button>
        <button class="hero-nav hero-nav--next" type="button"
                aria-label="{{ data_get($messages, 'media.next') }}" data-hero-next>
            <span aria-hidden="true">{{ $dir === 'rtl' ? '‹' : '›' }}</span>
        </button>
        <div class="hero-dots" role="group" aria-label="{{ data_get($messages, 'media.open') }}">
            @foreach ($heroSlides as $index => $item)
                <button type="button" class="{{ $index === 0 ? 'is-active' : '' }}"
                        aria-label="{{ $index + 1 }}. {{ data_get($messages, 'gallery.showImage') }}"
                        aria-current="{{ $index === 0 ? 'true' : 'false' }}" data-hero-dot="{{ $index }}"></button>
            @endforeach
        </div>
    @endif
</section>
