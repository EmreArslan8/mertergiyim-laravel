<header class="page-heading {{ $class ?? '' }}">
    @if (! empty($eyebrow))
        <span class="page-heading-eyebrow">{{ $eyebrow }}</span>
    @endif
    <h1>{{ $title ?? '' }}</h1>
    @if (! empty($description))
        <p>{{ $description }}</p>
    @endif
</header>
