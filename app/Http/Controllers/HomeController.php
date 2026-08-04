<?php

namespace App\Http\Controllers;

use App\Services\ProductCardService;
use App\Services\StorefrontRepository;
use App\Support\Storefront;
use Illuminate\View\View;

/**
 * app/[locale]/page.tsx karşılığı.
 */
class HomeController extends Controller
{
    public function __construct(
        private StorefrontRepository $repository,
        private ProductCardService $cards,
    ) {}

    public function __invoke(): View
    {
        $locale = app()->getLocale();
        $data = $this->repository->home();
        $heroSlides = collect($data['slides'])
            ->map(fn ($slide): array => [
                'record' => $slide,
                'image' => Storefront::imageUrl('site', $slide->image_path, 1920, 80),
            ])
            ->values()
            ->all();
        $hero = data_get($heroSlides, '0.record');
        $heroImage = (string) data_get($heroSlides, '0.image', '');
        $localizedSettings = array_replace(
            (array) data_get($data, 'settings.tr', []),
            (array) data_get($data, 'settings.'.$locale, []),
        );

        $viewData = [
            'hero' => $hero,
            'heroSlides' => $heroSlides,
            // Canlı referans hero kalitesi: 1920px, quality 80.
            'heroImage' => $heroImage,
            'cards' => $this->cards->make($data['products'], $locale),
            'canonicalPath' => Storefront::localePath($locale),
            'alternatePath' => fn (string $code) => Storefront::localePath($code),
        ];

        foreach ([
            'homeSeoTitle' => 'metaTitle',
            'homeSeoDescription' => 'metaDescription',
            'homeSeoKeywords' => 'metaKeywords',
        ] as $settingKey => $viewKey) {
            $value = trim((string) ($localizedSettings[$settingKey] ?? ''));

            if ($value !== '') {
                $viewData[$viewKey] = $value;
            }
        }

        $shareImage = trim((string) data_get($data, 'settings.general.homeSeoShareImage', ''));

        if ($shareImage !== '') {
            $viewData['ogImage'] = Storefront::storageUrl('site', $shareImage);
        }

        return view('storefront.home', $viewData);
    }
}
