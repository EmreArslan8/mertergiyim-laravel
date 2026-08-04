<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Support\Multilingual;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ExchangeRateService;
use App\Support\OrderStatus;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

/**
 * Sipariş kalemi alanları ve kargo firması seçimi.
 *
 * Hem yeni sipariş formu hem de detay sayfasındaki "Kalemleri düzenle" modalı
 * aynı alanları kullanır: ürün katalogdan seçilir, beden/renk o ürünün
 * varyantlarından gelir, satır toplamı elle girilmez.
 */
class OrderItems
{
    /**
     * Repeater içindeki tek bir kalemin alanları.
     *
     * @return array<int, mixed>
     */
    public static function fields(): array
    {
        return [
            // Ürün adı/kodu kalemin içine kopyalanır: ürün sonradan silinse
            // veya adı değişse bile sipariş o günkü hâliyle okunabilir kalır.
            Hidden::make('product_name'),
            Hidden::make('product_code'),
            Hidden::make('size'),
            Hidden::make('color'),

            // Birim fiyat elle girilmez, katalogdan gelir: fiyatı iki yerde
            // tutmak, ürün fiyatı değişince siparişte sessizce eskimeye yol
            // açıyordu. Alan gizli, seçimle birlikte doldurulur.
            Hidden::make('unit_price'),

            // Ürün listesi aramalı değil: katalog panelden yönetiliyor ve
            // aramalı select yazmadan seçenek göstermiyordu.
            Select::make('product_id')
                ->label('Ürün')
                ->columnSpan(['default' => 1, 'md' => 6])
                ->required()
                ->options(fn (): array => self::productOptions())
                ->live()
                ->afterStateUpdated(function ($state, $set): void {
                    $product = Product::query()->find($state);

                    $set('product_name', $product ? Multilingual::tr($product->name) : null);
                    $set('product_code', $product?->code);
                    $set('unit_price', $product === null ? null : self::catalogPrice($product));
                    // Ürün değişince eski varyant geçersiz.
                    $set('variant_id', null);
                    $set('size', null);
                    $set('color', null);
                }),

            Select::make('variant_id')
                ->label('Beden / renk')
                ->columnSpan(['default' => 1, 'md' => 4])
                ->placeholder('Varyant seçilmedi')
                ->options(fn ($get): array => self::variantOptions($get('product_id')))
                ->live()
                ->afterStateUpdated(function ($state, $set): void {
                    $variant = ProductVariant::query()->with(['size', 'color'])->find($state);

                    $set('size', $variant?->size?->name);
                    $set('color', $variant?->color?->name);
                }),

            TextInput::make('quantity')
                ->label('Adet')
                ->columnSpan(['default' => 1, 'md' => 2])
                ->numeric()
                ->minValue(1)
                ->default(1)
                ->required(),
        ];
    }

    /**
     * Form verisinden veritabanı satırı: satır toplamı adet × birim fiyat.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function attributes(array $item): array
    {
        $quantity = max(1, (int) ($item['quantity'] ?? 1));

        // Fiyat formda gizli alanda taşınır; herhangi bir sebeple boş gelirse
        // (eski kayıt, programatik oluşturma) katalogdan okunur.
        $unitPrice = filled($item['unit_price'] ?? null)
            ? (float) $item['unit_price']
            : self::catalogPrice(Product::query()->find($item['product_id'] ?? null));

        $unitPrice = $unitPrice === null ? null : (float) $unitPrice;

        return [
            'product_id' => $item['product_id'] ?? null,
            'variant_id' => $item['variant_id'] ?? null,
            'product_name' => $item['product_name'] ?? '',
            'product_code' => $item['product_code'] ?? null,
            'size' => $item['size'] ?? null,
            'color' => $item['color'] ?? null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $unitPrice === null ? null : $unitPrice * $quantity,
        ];
    }

    /** Ürünün yönetim panelindeki siparişler için TL karşılığı. */
    private static function catalogPrice(?Product $product): ?float
    {
        if ($product && filled($product->price_usd)) {
            try {
                return (float) $product->priceForLocale(
                    'tr',
                    app(ExchangeRateService::class)->ratesFromTry(),
                );
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * Sipariş toplamı kalemlerden türetilir, elle girilmez.
     */
    public static function recalculateTotal(Order $order): void
    {
        $order->forceFill([
            'total' => $order->items()->sum('line_total') ?: null,
        ])->save();
    }

    /**
     * Kargo firması: sabit liste + daha önce elle girilmiş firmalar.
     * Listede olmayan bir firma "+" ile eklenebilir.
     */
    public static function cargoCompanySelect(): Select
    {
        return Select::make('cargo_company')
            ->label('Kargo firması')
            ->options(fn (): array => self::cargoCompanyOptions())
            ->placeholder('Kargo firması seçilmedi')
            // Sipariş hazırlanmaya başlamadan kargo firması seçilemez.
            ->disabled(fn ($get): bool => ! OrderStatus::allowsCargo($get('status')))
            ->helperText(fn ($get): ?string => OrderStatus::allowsCargo($get('status'))
                ? null
                : 'Sipariş "Hazırlanıyor" durumuna geçince girilebilir.')
            ->createOptionForm([
                TextInput::make('name')->label('Kargo firması')->required(),
            ])
            ->createOptionUsing(fn (array $data): string => $data['name'])
            ->createOptionModalHeading('Yeni kargo firması')
            ->live();
    }

    /**
     * @return array<string, string>
     */
    private static function cargoCompanyOptions(): array
    {
        $used = Order::query()
            ->whereNotNull('cargo_company')
            ->distinct()
            ->pluck('cargo_company')
            ->all();

        $all = collect([...array_keys(OrderForm::CARGO_COMPANIES), ...$used])
            ->filter()
            ->unique()
            ->sort(SORT_FLAG_CASE | SORT_NATURAL)
            ->values()
            ->all();

        return array_combine($all, $all);
    }

    /**
     * Ürün seçenekleri: "Ürün adı · KOD".
     *
     * @return array<string, string>
     */
    private static function productOptions(): array
    {
        return Product::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->mapWithKeys(fn (Product $product): array => [
                $product->getKey() => Multilingual::tr($product->name).' · '.$product->code,
            ])
            ->all();
    }

    /**
     * "5'li", "10'lu", "24'lü" — sayının okunuşuna göre ek.
     *
     * Sipariş detayındaki kalem listesi de bu etiketi kullanır.
     *
     * Türkçe ünlü uyumu son hecenin ünlüsüne bakar; 10, 20, 100 gibi sayılarda
     * son hece rakamın kendisi değil onluk/yüzlük adıdır (on'lu, yirmi'li).
     */
    public static function packLabel(int $size): string
    {
        $byDigit = [1 => 'li', 2 => 'li', 3 => 'lü', 4 => 'lü', 5 => 'li', 6 => 'lı', 7 => 'li', 8 => 'li', 9 => 'lu'];
        $byTens = [1 => 'lu', 2 => 'li', 3 => 'lu', 4 => 'lı', 5 => 'li', 6 => 'lı', 7 => 'li', 8 => 'li', 9 => 'lı'];

        $lastDigit = $size % 10;

        $suffix = match (true) {
            $lastDigit !== 0 => $byDigit[$lastDigit],
            $size % 100 !== 0 => $byTens[intdiv($size % 100, 10)],
            $size % 1000 !== 0 => 'lü', // yüz
            default => 'li', // bin
        };

        return $size."'".$suffix;
    }

    /**
     * Seçili ürünün varyantları: "M · Siyah (12 adet)".
     *
     * @return array<string, string>
     */
    private static function variantOptions(mixed $productId): array
    {
        if (blank($productId)) {
            return [];
        }

        $packSize = (int) (Product::query()->find($productId)?->pack_size ?? 1);

        return ProductVariant::query()
            ->with(['size', 'color'])
            ->where('product_id', $productId)
            ->get()
            ->mapWithKeys(function (ProductVariant $variant) use ($packSize): array {
                $label = collect([$variant->size?->name, $variant->color?->name])
                    ->filter()
                    ->join(' · ') ?: 'Varyant';

                // Yalnızca satış birimi yazılır: ürün seri hâlinde satılıyorsa
                // seri büyüklüğü ("5'li seri"). Stok adedi buraya konmuyordu:
                // seri ürün seviyesinde, stok beden seviyesinde bir sayı;
                // ikisi yan yana yazılınca hangisinin ne olduğu okunmuyordu.
                if ($packSize <= 1) {
                    return [$variant->getKey() => $label];
                }

                return [$variant->getKey() => $label.' · '.self::packLabel($packSize).' seri'];
            })
            ->all();
    }
}
