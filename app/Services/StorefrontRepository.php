<?php

namespace App\Services;

use App\Models\Category;
use App\Models\BlogPost;
use App\Models\ContentPage;
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
            'categories' => Category::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'name_i18n', 'slug'])->all(),
        ]);
    }

    /**
     * Ana sayfa verisi (getStorefrontHomeData).
     */
    public function home(): array
    {
        return $this->remember('home', fn () => [
            'products' => Product::query()->active()->with(['images', 'category'])->orderBy('created_at')->get()->all(),
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
                'recommendations' => Product::query()->active()->with(['images', 'category'])
                    ->whereKeyNot($product->id)
                    ->orderByDesc('created_at')
                    ->limit(2)
                    ->get()
                    ->all(),
            ];
        });

        return $data['product'] ? $data : null;
    }

    public function contentPage(string $slug): ?ContentPage
    {
        return $this->remember('page:'.$slug, fn () => ContentPage::query()
            ->where('active', true)
            ->where('slug', $slug)
            ->first());
    }

    public function blogPosts(): array
    {
        return $this->remember('blog', fn () => BlogPost::query()
            ->where('active', true)
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderByDesc('published_at')
            ->get()
            ->all());
    }

    public function blogPost(string $slug): ?BlogPost
    {
        return $this->remember('blog:'.$slug, fn () => BlogPost::query()
            ->where('active', true)
            ->where('slug', $slug)
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->first());
    }

    /**
     * Ürünün beden/renk seçenekleri; varyantlardan tekilleştirilir.
     */
    public function productOptions(Product $product, string $locale): array
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

        // Etiket çevrilir ama sepete/siparişe id gider: farklı dillerde çevrilmiş
        // ad ile Türkçe veritabanı değeri karşılaştırılınca varyant bulunamıyordu.
        return [
            'sizes' => array_map(fn ($size) => [
                'id' => $size->id,
                'name' => Storefront::text($size->name_i18n, $locale) ?: $size->name,
            ], $sizes),
            'colors' => array_map(fn ($color) => [
                'id' => $color->id,
                'name' => Storefront::text($color->name_i18n, $locale) ?: $color->name,
                'hex' => $color->hex,
            ], $colors),
        ];
    }

    /**
     * track_order RPC'sinin Eloquent karşılığı: sipariş no VEYA takip kodu.
     *
     * Müşteri kodu tire/boşluk olmadan da yazabildiği için giriş normalize edilip
     * kayıtlı biçimlere geri kuruluyor; sorgu order_number ve tracking_code
     * indeksleri üzerinden tek seferde çalışır (tüm tabloyu çekmez).
     */
    public function trackOrder(string $query): ?array
    {
        $candidates = $this->trackingCandidates($query);

        if ($candidates === []) {
            return null;
        }

        $order = Order::query()
            ->with(['items' => fn ($items) => $items->orderBy('created_at')])
            ->where(fn ($builder) => $builder
                ->whereIn('order_number', $candidates)
                ->orWhereIn('tracking_code', $candidates))
            ->first();

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
     * Kullanıcının yazdığı takip girdisinden aranacak tam değerleri üretir.
     *
     * "mg 20260728 a1b2c3", "MG-20260728-A1B2C3" ve "mg20260728a1b2c3" aynı
     * siparişi bulur; kodlar OrderCodeService'te büyük harf üretildiği için
     * karşılaştırma büyük harfe çevrilmiş biçimler üzerinden yapılır.
     *
     * @return array<int, string>
     */
    private function trackingCandidates(string $query): array
    {
        $trimmed = trim($query);
        $bare = strtoupper((string) preg_replace('/[^a-zA-Z0-9]/', '', $trimmed));

        if ($bare === '') {
            return [];
        }

        $candidates = [strtoupper($trimmed), $bare];

        // Tiresiz yazılan sipariş numarasını kayıtlı "MG-YYYYAAGG-XXXXXX" biçimine çevir.
        if (preg_match('/^([A-Z]{2,4})(\d{8})([A-Z0-9]{4,10})$/', $bare, $match)) {
            $candidates[] = $match[1].'-'.$match[2].'-'.$match[3];
        }

        return array_values(array_unique($candidates));
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
