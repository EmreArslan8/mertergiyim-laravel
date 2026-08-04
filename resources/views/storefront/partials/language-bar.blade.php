{{-- LanguageBar (StorefrontSections.tsx) --}}
<div class="language-bar">
    <div class="language-bar-inner">
        <strong>{{ $messages['common']['language'] ?? '' }}:</strong>
        <div class="language-options">
            @foreach ($languages as $language)
                <a class="{{ $language->code === $locale ? 'selected' : '' }}" href="{{ isset($alternatePath) ? $alternatePath($language->code) : \App\Support\Storefront::localePath($language->code) }}">{{ $language->name }}</a>
            @endforeach
        </div>
    </div>
</div>
