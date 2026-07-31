{{-- Kayıt sayfalarında başlığın üstünde, solda duran "listeye dön" butonu.
     Tema breadcrumb'ları gizlediği için geri dönüşün tek görünür yolu bu. --}}
<a href="{{ $url }}" class="merter-back-link" wire:navigate>
    <x-filament::icon icon="heroicon-m-arrow-left" class="merter-back-link-icon" />
    <span>{{ $label }}</span>
</a>
