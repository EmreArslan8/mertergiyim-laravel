<?php

namespace App\Http\Controllers;

use App\Services\StorefrontRepository;
use Illuminate\View\View;

class ContentPageController extends Controller
{
    public function __construct(private StorefrontRepository $repository) {}

    public function __invoke(string $locale, string $slug): View
    {
        $page = $this->repository->contentPage($slug);
        abort_if(! $page, 404);

        return view('storefront.content-page', [
            'page' => $page,
            'canonicalPath' => '/'.$locale.'/'.$page->slug,
            'alternatePath' => fn (string $code) => '/'.$code.'/'.$page->slug,
        ]);
    }
}
