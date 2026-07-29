@php
    $sizes = app(\App\Services\AdminOptionService::class)->sizes();
    $colors = app(\App\Services\AdminOptionService::class)->colorDetails();
    $selectedSizeIds = array_values(array_filter((array) $get('variant_size_ids')));
    $selectedColorIds = array_values(array_filter((array) $get('variant_color_ids')));
    $variants = collect((array) $get('variants'));
    $ratios = collect((array) $get('pack_contents'))
        ->filter(fn (array $item): bool => filled($item['size_id'] ?? null) && (int) ($item['quantity'] ?? 0) > 0)
        ->pluck('quantity', 'size_id')
        ->map(fn ($quantity): int => (int) $quantity)
        ->all();
    $isPack = (int) $get('pack_size') > 1 && $ratios !== [];

    $rows = collect($selectedColorIds)->map(function ($colorId) use ($colors, $selectedSizeIds, $variants): array {
        $cells = collect($selectedSizeIds)->mapWithKeys(function ($sizeId) use ($colorId, $variants): array {
            $variantKey = $variants->search(fn (array $variant): bool =>
                (string) ($variant['color_id'] ?? '') === (string) $colorId
                && (string) ($variant['size_id'] ?? '') === (string) $sizeId
            );

            if ($variantKey === false) {
                return [$sizeId => null];
            }

            return [$sizeId => [
                'key' => $variantKey,
                'stock' => max(0, (int) ($variants->get($variantKey)['stock_quantity'] ?? 0)),
            ]];
        })->all();

        return [
            'id' => $colorId,
            'name' => $colors[$colorId]['name'] ?? 'Renk',
            'hex' => $colors[$colorId]['hex'] ?? '#ffffff',
            'cells' => $cells,
        ];
    });
@endphp

<div class="merter-stock-matrix">
    <div class="merter-stock-matrix__heading">
        <div>
            <strong>Renk × beden stokları</strong>
            <span>Her hücreye depodaki gerçek ürün adedini girin.</span>
        </div>
        <span class="merter-stock-matrix__unit">Stok birimi: adet</span>
    </div>

    @if ($selectedSizeIds === [] || $selectedColorIds === [])
        <div class="merter-stock-matrix__empty">
            Stok tablosunu oluşturmak için önce en az bir beden ve renk seçin.
        </div>
    @else
        <div class="merter-stock-matrix__scroll">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Renk</th>
                        @foreach ($selectedSizeIds as $sizeId)
                            <th scope="col">{{ $sizes[$sizeId] ?? 'Beden' }}</th>
                        @endforeach
                        <th scope="col">Satılabilir</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="merter-stock-matrix__ratio">
                        <th scope="row">Paket oranı</th>
                        @foreach ($selectedSizeIds as $sizeId)
                            <td>{{ $ratios[$sizeId] ?? '—' }}</td>
                        @endforeach
                        <td>{{ $isPack ? ((int) $get('pack_size')).' adet' : 'Tekli' }}</td>
                    </tr>

                    @foreach ($rows as $row)
                        @php
                            $initialStocks = collect($row['cells'])->mapWithKeys(
                                fn ($cell, $sizeId): array => [$sizeId => (int) ($cell['stock'] ?? 0)]
                            )->all();
                        @endphp
                        <tr
                            x-data="{
                                stocks: @js($initialStocks),
                                ratios: @js($ratios),
                                isPack: @js($isPack),
                                available() {
                                    if (! this.isPack) return null

                                    const values = Object.keys(this.ratios).map((sizeId) => {
                                        const ratio = Number(this.ratios[sizeId]) || 1

                                        return Math.floor((Number(this.stocks[sizeId]) || 0) / ratio)
                                    })

                                    return values.length ? Math.min(...values) : 0
                                },
                            }"
                        >
                            <th scope="row">
                                <span class="merter-color-option">
                                    <span
                                        class="merter-color-option__swatch"
                                        style="--merter-option-color: {{ $row['hex'] }}"
                                    ></span>
                                    <span>{{ $row['name'] }}</span>
                                </span>
                            </th>

                            @foreach ($selectedSizeIds as $sizeId)
                                @php($cell = $row['cells'][$sizeId] ?? null)
                                <td>
                                    @if ($cell)
                                        <label class="sr-only" for="stock-{{ $row['id'] }}-{{ $sizeId }}">
                                            {{ $row['name'] }} {{ $sizes[$sizeId] ?? 'beden' }} stoğu
                                        </label>
                                        <input
                                            id="stock-{{ $row['id'] }}-{{ $sizeId }}"
                                            type="number"
                                            min="0"
                                            step="1"
                                            inputmode="numeric"
                                            x-model.number="stocks['{{ $sizeId }}']"
                                            wire:model.blur="data.variants.{{ $cell['key'] }}.stock_quantity"
                                        >
                                    @else
                                        <span class="merter-stock-matrix__missing">—</span>
                                    @endif
                                </td>
                            @endforeach

                            <td class="merter-stock-matrix__available">
                                <template x-if="isPack">
                                    <span><b x-text="available()"></b> paket</span>
                                </template>
                                <template x-if="! isPack">
                                    <span>—</span>
                                </template>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="merter-stock-matrix__note">
            Satılabilir paket sayısı, paketteki beden oranına göre en düşük stoktan otomatik hesaplanır.
        </p>
    @endif
</div>
