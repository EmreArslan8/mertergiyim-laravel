<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Support\BankCatalog;
use App\Support\Storefront;
use Illuminate\View\View;

class BankAccountsController extends Controller
{
    public function __invoke(): View
    {
        $locale = app()->getLocale();

        $accounts = BankAccount::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get()
            ->map(fn (BankAccount $account) => [
                'bank_name' => $account->bank_name,
                'logo' => BankCatalog::logoDataUri($account->bank_name),
                'account_type' => trim((string) $account->account_type),
                'account_holder' => trim((string) $account->account_holder),
                'iban' => Storefront::formatIban($account->iban),
                'branch' => trim((string) $account->branch),
            ]);

        return view('storefront.bank-accounts', [
            'accounts' => $accounts,
            'canonicalPath' => Storefront::localePath($locale, '/banka-hesaplarimiz'),
            'alternatePath' => fn (string $code) => Storefront::localePath($code, '/banka-hesaplarimiz'),
        ]);
    }
}
