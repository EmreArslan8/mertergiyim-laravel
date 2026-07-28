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

        $gallery = array_values(array_filter(array_map(
            fn ($image) => Storefront::storageUrl('products', $image->storage_path),
            Storefront::sortedImages($product->images),
        )));

        $recommendations = array_map(function ($item) use ($currencies, $locale, $rates) {
            $images = Storefront::sortedImages($item->images);

            return [
                'product' => $item,
                'image' => Storefront::storageUrl('products', $images[0]->storage_path ?? null),
                'price' => Storefront::formatPrice(
                    $item->priceForLocale($locale, $rates),
                    Storefront::resolveCurrency($currencies, $item->currencyForLocale($locale)),
                ),
            ];
        }, $data['recommendations']);

        $options = $this->repository->productOptions($product, $locale);

        return view('storefront.product', [
            'product' => $product,
            'productName' => Storefront::text($product->name, $locale),
            'price' => Storefront::formatPrice($numericPrice, $currency),
            'numericPrice' => (float) $numericPrice,
            'currencyCode' => $product->currencyForLocale($locale),
            'currencyDisplay' => $currency,
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
