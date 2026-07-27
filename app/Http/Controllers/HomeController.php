<?php

namespace App\Http\Controllers;

use App\Services\StorefrontRepository;
use App\Support\Storefront;
use Illuminate\View\View;

/**
 * app/[locale]/page.tsx karşılığı.
 */
class HomeController extends Controller
{
    public function __construct(private StorefrontRepository $repository) {}

    public function __invoke(string $locale): View
    {
        $data = $this->repository->home();
        $currencies = $this->repository->currencies();
        $hero = $data['slides'][0] ?? null;

        $cards = array_map(function ($product) use ($currencies) {
            $images = Storefront::sortedImages($product->images);
            $primary = Storefront::storageUrl('products', $images[0]->storage_path ?? null);

            return [
                'product' => $product,
                'primaryImage' => $primary,
                'secondaryImage' => Storefront::storageUrl('products', $images[1]->storage_path ?? null) ?: $primary,
                'currency' => Storefront::resolveCurrency($currencies, $product->currency),
            ];
        }, $data['products']);

        return view('storefront.home', [
            'hero' => $hero,
            'heroImage' => $hero ? Storefront::storageUrl('site', $hero->image_path) : '',
            'cards' => $cards,
            'canonicalPath' => '/'.$locale,
            'alternatePath' => fn (string $code) => '/'.$code,
        ]);
    }
}
