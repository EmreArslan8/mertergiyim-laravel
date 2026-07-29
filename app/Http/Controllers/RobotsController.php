<?php

namespace App\Http\Controllers;

use App\Support\BrandSettings;
use Illuminate\Http\Response;

/**
 * Kaynak projedeki app/robots.ts karşılığı.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $base = BrandSettings::siteUrl();
        $indexingEnabled = (bool) BrandSettings::general('searchIndexingEnabled', true);

        $body = implode("\n", [
            'User-agent: *',
            $indexingEnabled ? 'Allow: /' : 'Disallow: /',
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
