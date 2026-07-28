<?php

namespace App\Http\Controllers;

use App\Services\StorefrontRepository;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(private StorefrontRepository $repository) {}

    public function index(string $locale): View
    {
        return view('storefront.blog-index', [
            'posts' => $this->repository->blogPosts(),
            'canonicalPath' => '/'.$locale.'/blog',
            'alternatePath' => fn (string $code) => '/'.$code.'/blog',
        ]);
    }

    public function show(string $locale, string $slug): View
    {
        $post = $this->repository->blogPost($slug);
        abort_if(! $post, 404);

        return view('storefront.blog-show', [
            'post' => $post,
            'canonicalPath' => '/'.$locale.'/blog/'.$post->slug,
            'alternatePath' => fn (string $code) => '/'.$code.'/blog/'.$post->slug,
        ]);
    }
}
