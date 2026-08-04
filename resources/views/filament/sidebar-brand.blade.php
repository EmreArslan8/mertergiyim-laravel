@php
    $brandName = \App\Support\BrandSettings::name('tr');
    $brandLogo = \App\Support\BrandSettings::logoUrl();
@endphp

<div class="merter-brand">
    <div class="merter-brand-avatar" aria-hidden="true">
        @if ($brandLogo)
            <img src="{{ $brandLogo }}" alt="">
        @else
            <span>{{ mb_strtoupper(mb_substr($brandName, 0, 1)) }}</span>
        @endif
    </div>
    <div class="merter-brand-text">
        <strong>{{ $brandName }}</strong>
        <small>Yönetim paneli</small>
    </div>
</div>
