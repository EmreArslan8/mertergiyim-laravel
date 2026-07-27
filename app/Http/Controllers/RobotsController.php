<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Kaynak projedeki app/robots.ts karşılığı.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $base = rtrim((string) config('storefront.site_url'), '/');

        $body = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /livewire/',
            '',
            'Host: '.$base,
            'Sitemap: '.$base.'/sitemap.xml',
            '',
        ]);

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
