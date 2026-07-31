@php
    /**
     * Paket dağılımı: pakete her bedenden kaç adet gireceği.
     *
     * Stok tutulmuyor (canlıdaki davranışın aynısı): tabloda renk × beden
     * parça stoğu yok. Girilen tek sayı beden başına paket adedi; paket
     * toplamı bu satırın toplamından türer.
     */
    $sizes = app(\App\Services\AdminOptionService::class)->sizes();
    $colors = app(\App\Services\AdminOptionService::class)->colorDetails();
    $selectedSizeIds = array_values(array_filter((array) $get('variant_size_ids')));
    $selectedColorIds = array_values(array_filter((array) $get('variant_color_ids')));

    $packContents = collect((array) $get('pack_contents'));

    // Oran hücreleri doğrudan pack_contents satırına yazsın diye repeater
    // anahtarları beden id'siyle eşleştiriliyor (beden -> uuid).
    $ratioKeys = $packContents
        ->filter(fn (array $item): bool => filled($item['size_id'] ?? null))
        ->mapWithKeys(fn (array $item, string $key): array => [(string) $item['size_id'] => $key])
        ->all();

    $ratios = $packContents
        ->filter(fn (array $item): bool => filled($item['size_id'] ?? null))
        ->mapWithKeys(fn (array $item): array => [
            (string) $item['size_id'] => max(0, (int) ($item['quantity'] ?? 0)),
        ])
        ->all();

    $packTotal = array_sum($ratios);
    $selectedColors = array_map(fn ($colorId): array => [
        'name' => $colors[$colorId]['name'] ?? 'Renk',
        'hex' => $colors[$colorId]['hex'] ?? '#ffffff',
    ], $selectedColorIds);
@endphp

<div class="merter-stock-matrix" wire:key="product-pack-distribution-{{ md5(json_encode($selectedSizeIds)) }}">
    <div class="merter-stock-matrix__heading">
        <div>
            <strong>Paket dağılımı</strong>
            <span>Pakete her bedenden kaç adet gireceğini yazın. Paket adedi bu toplamdan hesaplanır.</span>
        </div>
        <div class="merter-stock-matrix__status">
            <span class="merter-stock-matrix__unit">Paket: {{ $packTotal ?: '—' }} adet</span>
        </div>
    </div>

    @if ($selectedSizeIds === [])
        <div class="merter-stock-matrix__empty">
            Paket dağılımını girmek için önce en az bir beden seçin.
        </div>
    @else
        <div class="merter-stock-matrix__scroll">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Beden</th>
                        @foreach ($selectedSizeIds as $sizeId)
                            <th scope="col">{{ $sizes[$sizeId] ?? 'Beden' }}</th>
                        @endforeach
                        <th scope="col">Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="merter-stock-matrix__ratio">
                        <th scope="row">Paket içindeki adet</th>
                        @foreach ($selectedSizeIds as $sizeId)
                            <td>
                                {{-- Sabit bilgi gibi görünür, tıklayınca düzenlenir. --}}
                                @if (isset($ratioKeys[$sizeId]))
                                    <label class="sr-only" for="ratio-{{ $sizeId }}">
                                        {{ $sizes[$sizeId] ?? 'Beden' }} paket içindeki adet
                                    </label>
                                    <input
                                        id="ratio-{{ $sizeId }}"
                                        class="merter-stock-matrix__ratio-input"
                                        type="number"
                                        min="1"
                                        step="1"
                                        inputmode="numeric"
                                        title="Pakete bu bedenden kaç adet giriyor — değiştirebilirsin"
                                        wire:model.live.debounce.400ms="data.pack_contents.{{ $ratioKeys[$sizeId] }}.quantity"
                                    >
                                @else
                                    —
                                @endif
                            </td>
                        @endforeach
                        <td>{{ $packTotal ?: '—' }} adet</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Mobilde tablo yerine ızgara: tek satırlık bir tablo için yatay
             kaydırma (min genişlik 42rem) gereksiz bir engel. Aynı alanlar,
             aynı `wire:model`, beden başına bir kutu. --}}
        <div class="merter-stock-matrix__grid">
            @foreach ($selectedSizeIds as $sizeId)
                <label class="merter-stock-cell" for="ratio-compact-{{ $sizeId }}">
                    <span class="merter-stock-cell__label">{{ $sizes[$sizeId] ?? 'Beden' }}</span>

                    @if (isset($ratioKeys[$sizeId]))
                        <input
                            id="ratio-compact-{{ $sizeId }}"
                            class="merter-stock-cell__input"
                            type="number"
                            min="1"
                            step="1"
                            inputmode="numeric"
                            wire:model.live.debounce.400ms="data.pack_contents.{{ $ratioKeys[$sizeId] }}.quantity"
                        >
                    @else
                        <span class="merter-stock-cell__input">—</span>
                    @endif
                </label>
            @endforeach

            <div class="merter-stock-cell merter-stock-cell--total">
                <span class="merter-stock-cell__label">Paket toplamı</span>
                <span class="merter-stock-cell__value">{{ $packTotal ?: '—' }} adet</span>
            </div>
        </div>

        <p class="merter-stock-matrix__note">
            Bu dağılım ürün sayfasında müşteriye <b>PAKET İÇERİĞİ</b> olarak gösterilir.
            @if ($selectedColors !== [])
                Aynı dağılım seçili
                <span class="merter-pack-colors">
                    @foreach ($selectedColors as $color)
                        <span class="merter-color-option">
                            <span
                                class="merter-color-option__swatch"
                                style="--merter-option-color: {{ $color['hex'] }}"
                            ></span>
                            <span>{{ $color['name'] }}</span>
                        </span>
                    @endforeach
                </span>
                renklerinin hepsi için geçerlidir.
            @endif
        </p>
    @endif
</div>
