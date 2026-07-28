<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CartController extends Controller
{
    public function __invoke(string $locale): View
    {
        return view('storefront.cart', [
            'canonicalPath' => '/'.$locale.'/sepet',
            'alternatePath' => fn (string $code) => '/'.$code.'/sepet',
        ]);
    }
}
