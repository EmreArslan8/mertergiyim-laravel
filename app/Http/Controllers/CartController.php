<?php

namespace App\Http\Controllers;

use App\Support\Storefront;

use App\Models\BankAccount;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __invoke(): View
    {
        $locale = app()->getLocale();
        return view('storefront.cart', [
            'hasBankAccounts' => BankAccount::query()->where('active', true)->exists(),
            'canonicalPath' => Storefront::localePath($locale, '/sepet'),
            'alternatePath' => fn (string $code) => Storefront::localePath($code, '/sepet'),
        ]);
    }
}
