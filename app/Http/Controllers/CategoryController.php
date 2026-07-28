<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductCardService;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private ProductCardService $cards) {}

    public function index(string $locale): View
    {
        $products = Product::query()
            ->active()
            ->with(['images', 'category'])
            ->latest('created_at')
            ->get();

        return view('storefront.categories', [
            'cards' => $this->cards->make($products, $locale),
            'canonicalPath' => '/'.$locale.'/kategori',
            'alternatePath' => fn (string $code) => '/'.$code.'/kategori',
        ]);
    }

    public function __invoke(string $locale, string $slug): View
    {
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
            'canonicalPath' => '/'.$locale.'/kategori/'.$category->slug,
            'alternatePath' => fn (string $code) => '/'.$code.'/kategori/'.$category->slug,
        ]);
    }
}
