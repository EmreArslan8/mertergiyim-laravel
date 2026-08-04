<?php

namespace App\Http\Controllers;

use App\Services\StorefrontRepository;
use App\Support\Storefront;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(private StorefrontRepository $repository) {}

    public function index(): View
    {
        $locale = app()->getLocale();
        return view('storefront.blog-index', [
            'posts' => $this->repository->blogPosts(),
            'canonicalPath' => Storefront::localePath($locale, '/blog'),
            'alternatePath' => fn (string $code) => Storefront::localePath($code, '/blog'),
        ]);
    }

    public function show(string $slug): View
    {
        $locale = app()->getLocale();
        $post = $this->repository->blogPost($slug);
        abort_if(! $post, 404);

        return view('storefront.blog-show', [
            'post' => $post,
            'canonicalPath' => Storefront::localePath($locale, '/blog/'.$post->slug),
            'alternatePath' => fn (string $code) => Storefront::localePath($code, '/blog/'.$post->slug),
        ]);
    }
}
