<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\View\View;

class MultimediaController extends Controller
{
    public function __invoke(string $locale): View
    {
        return view('storefront.multimedia', [
            'mediaItems' => Media::query()->where('active', true)->orderBy('sort_order')->get(),
            'canonicalPath' => '/'.$locale.'/multimedya',
            'alternatePath' => fn (string $code) => '/'.$code.'/multimedya',
        ]);
    }
}
