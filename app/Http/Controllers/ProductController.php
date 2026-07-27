<?php

namespace App\Http\Controllers;

use App\Services\StorefrontRepository;
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
        $currency = Storefront::resolveCurrency($currencies, $product->currency);

        $gallery = array_values(array_filter(array_map(
            fn ($image) => Storefront::storageUrl('products', $image->storage_path),
            Storefront::sortedImages($product->images),
        )));

        $recommendations = array_map(function ($item) use ($currencies) {
            $images = Storefront::sortedImages($item->images);

            return [
                'product' => $item,
                'image' => Storefront::storageUrl('products', $images[0]->storage_path ?? null),
                'price' => Storefront::formatPrice($item->price, Storefront::resolveCurrency($currencies, $item->currency)),
            ];
        }, $data['recommendations']);

        return view('storefront.product', [
            'product' => $product,
            'productName' => Storefront::text($product->name, $locale),
            'price' => Storefront::formatPrice($product->price, $currency),
            'colors' => $this->repository->productOptions($product)['colors'],
            'gallery' => $gallery,
            'recommendations' => $recommendations,
            'videoEmbedUrl' => Storefront::youtubeEmbedUrl($product->video_url),
            'canonicalPath' => Storefront::productHref($locale, $product->slug),
            'alternatePath' => fn (string $code) => Storefront::productHref($code, $product->slug),
        ]);
    }
}
