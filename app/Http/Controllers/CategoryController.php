<?php

namespace App\Http\Controllers;

use App\Support\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductCardService;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private ProductCardService $cards) {}

    public function index(): View
    {
        $locale = app()->getLocale();
        $products = Product::query()
            ->active()
            ->with(['images', 'category'])
            ->latest('created_at')
            ->get();

        return view('storefront.categories', [
            'cards' => $this->cards->make($products, $locale),
            'canonicalPath' => Storefront::localePath($locale, '/kategori'),
            'alternatePath' => fn (string $code) => Storefront::localePath($code, '/kategori'),
        ]);
    }

    public function __invoke(string $slug): View
    {
        $locale = app()->getLocale();
        $category = Category::query()
            ->where('active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        $products = $category->products()
            ->active()
            ->with(['images', 'category'])
            ->latest('created_at')
            ->get();

        return view('storefront.category', [
            'category' => $category,
            'cards' => $this->cards->make($products, $locale),
            'canonicalPath' => Storefront::localePath($locale, '/kategori/'.$category->slug),
            'alternatePath' => fn (string $code) => Storefront::localePath($code, '/kategori/'.$category->slug),
        ]);
    }
}
