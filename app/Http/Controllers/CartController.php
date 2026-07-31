<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __invoke(string $locale): View
    {
        return view('storefront.cart', [
            'bankAccountsPage' => ContentPage::query()
                ->where('slug', 'banka-hesaplarimiz')
                ->where('active', true)
                ->exists(),
            'canonicalPath' => '/'.$locale.'/sepet',
            'alternatePath' => fn (string $code) => '/'.$code.'/sepet',
        ]);
    }
}
