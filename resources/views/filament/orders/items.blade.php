@php
    use App\Filament\Resources\Orders\Schemas\OrderItems;
    use App\Support\Storefront;

    $record = $getRecord();
    // Görsel ve paket bilgisi ürün üzerinden okunuyor: satır başına ayrı
    // sorgu atılmasın diye ilişkiler birlikte yüklenir.
    $items = $record?->items()->with(['product.images'])->get() ?? collect();

    $symbol = match ($record?->currency ?? 'TRY') {
        'USD' => ['symbol' => '$', 'position' => 'prefix'],
        'EUR' => ['symbol' => '€', 'position' => 'suffix'],
        default => ['symbol' => 'TL', 'position' => 'suffix'],
    };

    $price = fn ($value) => $value === null ? '—' : Storefront::formatPrice($value, $symbol);

    // Satır toplamı boş olabilir (eski siparişler): adet × birim fiyattan tamamlanır.
    $lineTotal = fn ($item) => $item->line_total ?? ($item->unit_price === null
        ? null
        : $item->unit_price * $item->quantity);

    $itemsTotal = $items->sum(fn ($item) => (float) ($lineTotal($item) ?? 0));
    $storedTotal = $record?->total === null ? null : (float) $record->total;
    // Kayıtlı toplam ile kalemlerden hesaplanan toplam ayrışabilir; kuruş
    // farkları yuvarlamadan geliyor, 1 TL üstü fark gerçek uyuşmazlıktır.
    $mismatch = $storedTotal !== null && $itemsTotal > 0 && abs($storedTotal - $itemsTotal) >= 1;
@endphp

<div class="merter-order-items">
    @if ($items->isEmpty())
        <p class="merter-order-empty">Bu siparişte kalem yok.</p>
    @else
        <div class="merter-order-items-scroll">
            <table class="merter-order-items-table">
                <thead>
                    <tr>
                        <th class="merter-order-items-thumb-col">Görsel</th>
                        <th>Ürün adı</th>
                        <th>Ürün kodu</th>
                        <th class="merter-order-items-num">Adet</th>
                        <th class="merter-order-items-num">Birim fiyat</th>
                        <th class="merter-order-items-num">Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td class="merter-order-items-thumb-col merter-order-cell-thumb">
                                @if ($image = $item->product?->images->first()?->storage_path)
                                    <img
                                        src="{{ Storefront::storageUrl('products', $image) }}"
                                        alt=""
                                        loading="lazy"
                                        class="merter-order-item-thumb"
                                    >
                                @else
                                    <span class="merter-order-item-thumb merter-order-item-thumb-empty" aria-hidden="true"></span>
                                @endif
                            </td>
                            <td class="merter-order-cell-name">
                                <span class="merter-order-item-name">{{ $item->product_name }}</span>
                                @php
                                    // Beden/renk kalemin içinde saklı; seri
                                    // büyüklüğü ürünün paket adedinden gelir.
                                    $packSize = (int) ($item->product?->pack_size ?? 1);

                                    $meta = collect([
                                        $item->size,
                                        $item->color,
                                        $packSize > 1 ? OrderItems::packLabel($packSize).' seri' : null,
                                    ])->filter()->join(' · ');
                                @endphp

                                @if ($meta !== '')
                                    <span class="merter-order-item-meta">{{ $meta }}</span>
                                @endif
                            </td>
                            <td class="merter-order-cell-code" data-label="Ürün kodu">
                                <span class="merter-order-item-code">{{ $item->product_code ?: '—' }}</span>
                            </td>
                            {{-- data-label mobilde başlık satırı gizlenince
                                 hücrenin üstünde etiket olarak yazılır. --}}
                            <td class="merter-order-items-num merter-order-cell-qty" data-label="Adet">{{ $item->quantity }}</td>
                            <td class="merter-order-items-num merter-order-cell-unit" data-label="Birim fiyat">{{ $price($item->unit_price) }}</td>
                            <td class="merter-order-items-num merter-order-cell-total merter-order-item-total" data-label="Toplam">{{ $price($lineTotal($item)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="merter-order-totals">
            <div class="merter-order-total-row">
                <span>Kalemler toplamı</span>
                <span>{{ $price($itemsTotal) }}</span>
            </div>
            <div class="merter-order-total-row merter-order-total-grand">
                <span>Genel toplam</span>
                <span>{{ $price($storedTotal ?? $itemsTotal) }}</span>
            </div>

            @if ($mismatch)
                <p class="merter-order-total-warning">
                    Siparişte kayıtlı toplam ({{ $price($storedTotal) }}) kalemlerin toplamıyla
                    ({{ $price($itemsTotal) }}) uyuşmuyor.
                </p>
            @endif
        </div>
    @endif
</div>
