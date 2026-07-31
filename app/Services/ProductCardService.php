<?php

namespace App\Services;

use App\Models\Product;
use App\Support\Storefront;
use Illuminate\Support\Collection;

class ProductCardService
{
    public function __construct(
        private StorefrontRepository $repository,
        private ExchangeRateService $exchangeRates,
    ) {}

    /**
     * @param  iterable<int, Product>  $products
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
            // Tüm görseller kart galerisinde bulunur; istemci yalnızca ilkini
            // başlangıçta, diğerlerini kullanıcı kaydırdıkça yükler.
            $cardImages = collect($images)
                ->map(fn ($image): string => Storefront::imageUrl('products', $image->storage_path, 600))
                ->filter()
                ->values()
                ->all();
            $primary = $cardImages[0] ?? '';

            return [
                'product' => $product,
                'images' => $cardImages,
                'primaryImage' => $primary,
                'secondaryImage' => $cardImages[1] ?? $primary,
                'price' => $product->priceForLocale($locale, $rates),
                'currency' => Storefront::resolveCurrency($currencies, $product->currencyForLocale($locale)),
            ];
        })->all();
    }
}
