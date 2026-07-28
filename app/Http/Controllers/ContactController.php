<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Services\DictionaryService;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(string $locale): View
    {
        return view('storefront.contact', [
            'canonicalPath' => '/'.$locale.'/iletisim',
            'alternatePath' => fn (string $code) => '/'.$code.'/iletisim',
        ]);
    }

    public function store(Request $request, string $locale): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => [
                'required',
                'string',
                'max:30',
                PhoneNumber::rule((string) app(DictionaryService::class)->get(
                    $locale,
                    'cart.errors.phone',
                    'Geçerli bir telefon numarası girin. Örnek: 0532 325 97 88',
                )),
            ],
            'email' => ['nullable', 'email', 'max:190'],
            'subject' => ['nullable', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ContactMessage::query()->create($data + ['locale' => $locale]);

        return back()->with('contact_success', true);
    }
}
