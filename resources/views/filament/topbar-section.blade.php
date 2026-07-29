@php
    $sections = [
        'filament.admin.pages.dashboard' => ['Kontrol Paneli', 'heroicon-o-home'],
        'filament.admin.resources.categories.*' => ['Kategoriler', 'heroicon-o-rectangle-stack'],
        'filament.admin.resources.languages.*' => ['Diller', 'heroicon-o-language'],
        'filament.admin.resources.sizes.*' => ['Bedenler', 'heroicon-o-arrows-pointing-out'],
        'filament.admin.resources.colors.*' => ['Renkler', 'heroicon-o-swatch'],
        'filament.admin.resources.currencies.*' => ['Para Birimi', 'heroicon-o-banknotes'],
        'filament.admin.resources.products.*' => ['Ürünler', 'heroicon-o-shopping-bag'],
        'filament.admin.resources.orders.*' => ['Siparişler', 'heroicon-o-clipboard-document-list'],
        'filament.admin.resources.hero-slides.*' => ['Slider', 'heroicon-o-photo'],
        'filament.admin.resources.site-links.*' => ['Site Linkleri', 'heroicon-o-link'],
        'filament.admin.resources.site-settings.*' => ['Site Ayarları', 'heroicon-o-cog-6-tooth'],
        'filament.admin.resources.translation-usages.*' => ['Çeviri Kullanımı', 'heroicon-o-chart-bar'],
        'filament.admin.resources.content-pages.*' => ['Bilgilendirme Sayfaları', 'heroicon-o-document-text'],
        'filament.admin.resources.blog-posts.*' => ['Blog Sayfaları', 'heroicon-o-newspaper'],
        'filament.admin.resources.media.*' => ['Multimedya', 'heroicon-o-play-circle'],
        'filament.admin.resources.admin-users.*' => ['Admin Ayarları', 'heroicon-o-shield-check'],
    ];

    $activeSection = collect($sections)->first(fn ($section, $route) => request()->routeIs($route));
@endphp

@if ($activeSection)
    <div class="merter-topbar-section">
        <x-filament::icon :icon="$activeSection[1]" />
        <strong>{{ $activeSection[0] }}</strong>
    </div>
@endif
