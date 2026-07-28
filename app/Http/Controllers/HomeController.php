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
            'heroImage' => $hero ? Storefront::storageUrl('site', $hero->image_path) : '',
            'cards' => $this->cards->make($data['products'], $locale),
            'canonicalPath' => '/'.$locale,
            'alternatePath' => fn (string $code) => '/'.$code,
        ]);
    }
}
