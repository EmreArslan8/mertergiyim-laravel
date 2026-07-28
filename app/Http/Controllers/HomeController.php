<?php

namespace App\Http\Controllers;

use App\Services\StorefrontRepository;
use App\Services\ProductCardService;
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

    public function __invoke(string $locale): View
    {
        $data = $this->repository->home();
        $hero = $data['slides'][0] ?? null;

        return view('storefront.home', [
            'hero' => $hero,
            // LCP görseli: tam ekran, 1600px retina dahil her ekrana yeter.
            // Koyu overlay + büyük başlık altında kaldığı için q65 gözle ayırt edilmiyor.
            'heroImage' => $hero ? Storefront::imageUrl('site', $hero->image_path, 1600, 65) : '',
            'cards' => $this->cards->make($data['products'], $locale),
            'canonicalPath' => '/'.$locale,
            'alternatePath' => fn (string $code) => '/'.$code,
        ]);
    }
}
