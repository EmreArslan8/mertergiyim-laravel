<?php

namespace App\Http\Controllers;

use App\Services\StorefrontRepository;
use App\Services\ExchangeRateService;
use App\Support\Storefront;
use Illuminate\View\View;

/**
 * app/[locale]/product/[slug]/page.tsx karşılığı.
 */
class ProductController extends Controller
{
    public function __construct(private StorefrontRepository $repository) {}

    public function __invoke(string $locale, string $slug): View
    {
        $data = $this->repository->product($slug);

        abort_if($data === null, 404);

        $product = $data['product'];
        $currencies = $this->repository->currencies();
        $rates = $locale === 'tr' ? null : rescue(
            fn () => app(ExchangeRateService::class)->ratesFromTry(),
            report: true,
        );
        $currency = Storefront::resolveCurrency($currencies, $product->currencyForLocale($locale));
        $numericPrice = $product->priceForLocale($locale, $rates);

        // Galeri görseli detay sayfasında büyük gösteriliyor; 1000px retina dahil yeter.
        $gallery = array_values(array_filter(array_map(
            fn ($image) => Storefront::imageUrl('products', $image->storage_path, 1000),
            Storefront::sortedImages($product->images),
        )));

        $recommendations = array_map(function ($item) use ($currencies, $locale, $rates) {
            $images = Storefront::sortedImages($item->images);

            return [
                'product' => $item,
                'image' => Storefront::imageUrl('products', $images[0]->storage_path ?? null, 600),
                'price' => Storefront::formatPrice(
                    $item->priceForLocale($locale, $rates),
                    Storefront::resolveCurrency($currencies, $item->currencyForLocale($locale)),
                ),
            ];
        }, $data['recommendations']);

        $options = $this->repository->productOptions($product, $locale);
        $packSize = max(1, (int) ($product->pack_size ?? 1));
        $sizeNames = collect($options['sizes'])->keyBy('id');
        $packageBreakdown = collect($product->pack_contents ?? [])
            ->map(function (array $item) use ($sizeNames): ?array {
                $size = $sizeNames->get($item['size_id'] ?? null);
                $quantity = max(0, (int) ($item['quantity'] ?? 0));

                if (! $size || $quantity < 1) {
                    return null;
                }

                return [
                    'name' => $size['name'],
                    'quantity' => $quantity,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return view('storefront.product', [
            'product' => $product,
            'productName' => Storefront::text($product->name, $locale),
            'productDescription' => Storefront::text($product->description, $locale),
            'categoryName' => $product->category
                ? (Storefront::text($product->category->name_i18n, $locale) ?: $product->category->name)
                : '',
            'price' => Storefront::formatPrice($numericPrice, $currency),
            'numericPrice' => (float) $numericPrice,
            'currencyCode' => $product->currencyForLocale($locale),
            'currencyDisplay' => $currency,
            'packSize' => $packSize,
            'packageBreakdown' => $packageBreakdown,
            'sizes' => $options['sizes'],
            'colors' => $options['colors'],
            'gallery' => $gallery,
            'primaryImage' => $gallery[0] ?? '',
            'recommendations' => $recommendations,
            'videoEmbedUrl' => Storefront::youtubeEmbedUrl($product->video_url),
            'canonicalPath' => Storefront::productHref($locale, $product->slug),
            'alternatePath' => fn (string $code) => Storefront::productHref($code, $product->slug),
        ]);
    }
}
