<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Currency;
use App\Models\HeroSlide;
use App\Models\Language;
use App\Models\Order;
use App\Models\Product;
use App\Models\SiteLink;
use App\Models\SiteSetting;
use App\Support\Storefront;
use Illuminate\Support\Facades\Cache;

/**
 * lib/storefront/queries.ts karşılığı.
 *
 * Veri dilden bağımsız olduğu için cache anahtarları da locale içermez;
 * çeviri seçimi view katmanında yapılır.
 */
class StorefrontRepository
{
    private function ttl(): int
    {
        return config('storefront.cache_ttl');
    }

    private function remember(string $key, callable $callback): mixed
    {
        return Cache::remember('storefront:'.$key, $this->ttl(), $callback);
    }

    /**
     * Kod -> gösterim haritası + varsayılan.
     */
    public function currencies(): array
    {
        return $this->remember('currencies', function () {
            $rows = Currency::query()->orderBy('sort_order')->get();

            $byCode = [];
            foreach ($rows as $row) {
                $byCode[$row->code] = Storefront::currencyDisplay($row);
            }

            $fallback = $rows->firstWhere('is_default', true) ?? $rows->first();

            return [
                'byCode' => $byCode,
                'fallback' => $fallback ? Storefront::currencyDisplay($fallback) : ['symbol' => 'TL', 'position' => 'suffix'],
            ];
        });
    }

    /**
     * Header/footer için ortak veri (getStorefrontChrome).
     */
    public function chrome(): array
    {
        return $this->remember('chrome', fn () => [
            'languages' => Language::query()->where('active', true)->orderBy('sort_order')->get(['code', 'name'])->all(),
            'settingValue' => SiteSetting::query()->find('storefront')?->value ?? [],
            'links' => SiteLink::query()->where('active', true)->orderBy('sort_order')->get()->all(),
            'categories' => Category::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'slug'])->all(),
        ]);
    }

    /**
     * Ana sayfa verisi (getStorefrontHomeData).
     */
    public function home(): array
    {
        return $this->remember('home', fn () => [
            'products' => Product::query()->active()->with('images')->orderBy('created_at')->get()->all(),
            'slides' => HeroSlide::query()->where('active', true)->orderBy('sort_order')->get()->all(),
        ]);
    }

    /**
     * Ürün sayfası verisi (getProductPageData). Bulunamazsa null.
     */
    public function product(string $slug): ?array
    {
        $data = $this->remember('product:'.$slug, function () use ($slug) {
            $product = Product::query()->active()->with(['images', 'variants.size', 'variants.color'])->where('slug', $slug)->first();

            if (! $product) {
                return ['product' => null];
            }

            return [
                'product' => $product,
                'recommendations' => Product::query()->active()->with('images')
                    ->whereKeyNot($product->id)
                    ->orderByDesc('created_at')
                    ->limit(2)
                    ->get()
                    ->all(),
            ];
        });

        return $data['product'] ? $data : null;
    }

    /**
     * Ürünün beden/renk seçenekleri; varyantlardan tekilleştirilir.
     */
    public function productOptions(Product $product): array
    {
        $sizes = [];
        $colors = [];

        foreach ($product->variants as $variant) {
            if ($variant->size) {
                $sizes[$variant->size->name] = $variant->size;
            }
            if ($variant->color) {
                $colors[$variant->color->name] = $variant->color;
            }
        }

        $bySortOrder = fn ($first, $second) => $first->sort_order <=> $second->sort_order;
        usort($sizes, $bySortOrder);
        usort($colors, $bySortOrder);

        return [
            'sizes' => array_map(fn ($size) => $size->name, $sizes),
            'colors' => array_map(fn ($color) => ['name' => $color->name, 'hex' => $color->hex], $colors),
        ];
    }

    /**
     * track_order RPC'sinin Eloquent karşılığı: sipariş no VEYA takip kodu,
     * alfanumerik dışı karakterler yok sayılarak eşleştirilir.
     */
    public function trackOrder(string $query): ?array
    {
        $needle = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $query));

        if ($needle === '') {
            return null;
        }

        $order = Order::query()
            ->with(['items' => fn ($items) => $items->orderBy('created_at')])
            ->get()
            ->first(function (Order $candidate) use ($needle) {
                $number = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string) $candidate->order_number));
                $code = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string) $candidate->tracking_code));

                return $number === $needle || ($code !== '' && $code === $needle);
            });

        if (! $order) {
            return null;
        }

        return [
            'order_number' => $order->order_number,
            'tracking_code' => $order->tracking_code,
            'customer_masked' => $this->maskName((string) $order->customer_name),
            'status' => $order->status,
            'total' => $order->total,
            'cargo_company' => $order->cargo_company,
            'cargo_tracking_url' => $order->cargo_tracking_url,
            'created_at' => $order->created_at,
            'items' => $order->items->all(),
        ];
    }

    /**
     * İlk harf + en az 3 yıldız (track_order ile aynı maskeleme).
     */
    private function maskName(string $name): string
    {
        if ($name === '') {
            return '';
        }

        return mb_substr($name, 0, 1).str_repeat('*', max(mb_strlen($name) - 1, 3));
    }
}
