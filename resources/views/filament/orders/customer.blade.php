@php
    $record = $getRecord();
    $name = trim((string) $record?->customer_name);
    $phone = trim((string) $record?->phone);

    // Vitrin telefonu kullanıcının yazdığı gibi saklıyor: "5355159198",
    // "0535 515 91 98", "+90 535..." hepsi geliyor. Arama ve WhatsApp
    // bağlantısı için ülke kodlu tek biçime indirilir.
    $digits = preg_replace('/\D+/', '', $phone);
    $international = match (true) {
        $digits === '' => null,
        str_starts_with($digits, '90') && strlen($digits) === 12 => $digits,
        str_starts_with($digits, '0') && strlen($digits) === 11 => '90'.substr($digits, 1),
        // Ülke kodu ve baştaki sıfır olmadan gelen 10 haneli numara.
        strlen($digits) === 10 => '90'.$digits,
        default => $digits,
    };

    // Ekranda yerel biçim okunaklı: 0535 515 91 98
    $display = $phone;

    if ($international !== null && str_starts_with($international, '90') && strlen($international) === 12) {
        $local = '0'.substr($international, 2);
        $display = trim(sprintf(
            '%s %s %s %s',
            substr($local, 0, 4),
            substr($local, 4, 3),
            substr($local, 7, 2),
            substr($local, 9, 2),
        ));
    }
@endphp

<div class="merter-order-customer">
    <div class="merter-order-field">
        <span class="merter-order-field-label">Ad soyad</span>
        <span class="merter-order-field-value">{{ $name ?: '—' }}</span>
    </div>

    <div class="merter-order-field">
        <span class="merter-order-field-label">Telefon</span>

        @if ($international)
            <a
                href="tel:+{{ $international }}"
                class="merter-order-field-value merter-order-field-link"
            >{{ $display }}</a>

            <div class="merter-order-customer-actions">
                <a href="tel:+{{ $international }}" class="merter-order-chip">Ara</a>

                <a
                    href="https://wa.me/{{ $international }}"
                    target="_blank"
                    rel="noopener"
                    class="merter-order-chip"
                >WhatsApp</a>
            </div>
        @else
            <span class="merter-order-field-value">—</span>
        @endif
    </div>
</div>
