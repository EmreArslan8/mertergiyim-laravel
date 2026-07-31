@php
    $record = $getRecord();
    $address = trim((string) $record?->address);
    $name = trim((string) $record?->customer_name);
    // Kargo etiketi için ad + adres birlikte kopyalanır.
    $clipboard = trim($name."\n".$address);
@endphp

<div class="merter-order-address merter-order-field" x-data="{ copied: false }">
    <span class="merter-order-field-label">Teslimat adresi</span>

    @if ($address === '')
        <p class="merter-order-empty">Adres girilmemiş.</p>
    @else
        {{-- Ad üstteki müşteri alanında zaten var; burada tekrarlanmaz.
             Kopyalanan metne yine de dahil: kargo etiketi ad + adres olarak
             yapıştırılıyor. --}}
        <p class="merter-order-address-body">{{ $address }}</p>

        <button
            type="button"
            class="merter-order-chip"
            x-on:click="
                navigator.clipboard.writeText(@js($clipboard));
                copied = true;
                setTimeout(() => copied = false, 1500)
            "
        >
            <span x-show="! copied">Adresi kopyala</span>
            <span x-show="copied" x-cloak>Kopyalandı</span>
        </button>
    @endif
</div>
