<?php

namespace App\Services;

use App\Support\Storefront;
use Illuminate\Support\Collection;

class ProductCardService
{
    public function __construct(
        private StorefrontRepository $repository,
        private ExchangeRateService $exchangeRates,
    ) {}

    /**
     * @param  iterable<int, \App\Models\Product>  $products
     * @return array<int, array<string, mixed>>
     */
    public function make(iterable $products, string $locale): array
    {
        $currencies = $this->repository->currencies();
        $rates = $locale === 'tr' ? null : rescue(
            fn () => $this->exchangeRates->ratesFromTry(),
            report: true,
        );

        return Collection::make($products)->map(function ($product) use ($currencies, $locale, $rates) {
            $images = Storefront::sortedImages($product->images);
            // Kart masaüstünde ~400px geniş; 600px retina için yeterli.
            $primary = Storefront::imageUrl('products', $images[0]->storage_path ?? null, 600);

            return [
                'product' => $product,
                'primaryImage' => $primary,
                'secondaryImage' => Storefront::imageUrl('products', $images[1]->storage_path ?? null, 600) ?: $primary,
                'price' => $product->priceForLocale($locale, $rates),
                'currency' => Storefront::resolveCurrency($currencies, $product->currencyForLocale($locale)),
            ];
        })->all();
    }
}
