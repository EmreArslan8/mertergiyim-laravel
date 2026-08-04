{{-- Sidebar'daki marka bloğunun yerini alır: kare/logo yok, yalnızca yazı. --}}
<a href="{{ \Filament\Facades\Filament::getUrl() }}" class="merter-topbar-brand">
    {{ \App\Support\BrandSettings::name('tr') }}
</a>
