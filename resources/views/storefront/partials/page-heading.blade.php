<header class="page-heading {{ $class ?? '' }}">
    @if (! empty($eyebrow))
        <span class="page-heading-eyebrow">{{ $eyebrow }}</span>
    @endif
    <h1>{{ $title ?? '' }}</h1>
    @if (! empty($descriptionHtml))
        <div class="page-heading-copy">{!! $descriptionHtml !!}</div>
    @elseif (! empty($description))
        <p>{{ $description }}</p>
    @endif
</header>
