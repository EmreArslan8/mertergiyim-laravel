<?php

namespace App\Http\Controllers;

use App\Support\Storefront;

use App\Models\ContactMessage;
use App\Services\DictionaryService;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        $locale = app()->getLocale();
        return view('storefront.contact', [
            'canonicalPath' => Storefront::localePath($locale, '/iletisim'),
            'alternatePath' => fn (string $code) => Storefront::localePath($code, '/iletisim'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $locale = app()->getLocale();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => [
                'required',
                'string',
                'max:30',
                PhoneNumber::rule((string) app(DictionaryService::class)->get(
                    $locale,
                    'cart.errors.phone',
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
