<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\ContentPage;
use App\Models\Product;
use App\Support\BrandSettings;
use App\Support\Storefront;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Kaynak projedeki app/sitemap.ts karşılığı: her locale için ana sayfa,
 * sipariş takibi ve aktif ürün detayları; hepsi hreflang alternatifleriyle.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('storefront:sitemap', config('storefront.cache_ttl'), function () {
            $base = BrandSettings::siteUrl();
            $locales = Storefront::locales();
            $slugs = Product::query()->active()->pluck('slug')->filter()->all();
            $pageSlugs = ContentPage::query()->where('active', true)->pluck('slug')->filter()->all();
            $postSlugs = BlogPost::query()->where('active', true)->pluck('slug')->filter()->all();
            $categorySlugs = Category::query()->where('active', true)->pluck('slug')->filter()->all();

            $paths = [
                ['path' => fn (string $locale) => Storefront::localePath($locale), 'freq' => 'daily', 'priority' => '1.0'],
                ['path' => fn (string $locale) => Storefront::localePath($locale, '/siparis-takibi'), 'freq' => 'monthly', 'priority' => '0.4'],
                ['path' => fn (string $locale) => Storefront::localePath($locale, '/blog'), 'freq' => 'weekly', 'priority' => '0.7'],
                ['path' => fn (string $locale) => Storefront::localePath($locale, '/multimedya'), 'freq' => 'weekly', 'priority' => '0.6'],
                ['path' => fn (string $locale) => Storefront::localePath($locale, '/iletisim'), 'freq' => 'monthly', 'priority' => '0.5'],
            ];

            foreach ($categorySlugs as $slug) {
                $paths[] = [
                    'path' => fn (string $locale) => Storefront::localePath($locale, '/kategori/'.$slug),
                    'freq' => 'weekly',
                    'priority' => '0.8',
                ];
            }

            foreach ($slugs as $slug) {
                $paths[] = [
                    'path' => fn (string $locale) => Storefront::productHref($locale, $slug),
                    'freq' => 'weekly',
                    'priority' => '0.8',
                ];
            }

            foreach ($pageSlugs as $slug) {
                $paths[] = [
                    'path' => fn (string $locale) => Storefront::localePath($locale, '/'.$slug),
                    'freq' => 'monthly',
                    'priority' => '0.6',
                ];
            }

            foreach ($postSlugs as $slug) {
                $paths[] = [
                    'path' => fn (string $locale) => Storefront::localePath($locale, '/blog/'.$slug),
                    'freq' => 'monthly',
                    'priority' => '0.6',
                ];
            }

            $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
            $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';

            foreach ($paths as $entry) {
                foreach ($locales as $locale) {
                    $lines[] = '  <url>';
                    $lines[] = '    <loc>'.e($base.($entry['path'])($locale)).'</loc>';

                    foreach ($locales as $alternate) {
                        $lines[] = '    <xhtml:link rel="alternate" hreflang="'.$alternate
                            .'" href="'.e($base.($entry['path'])($alternate)).'" />';
                    }

                    $lines[] = '    <changefreq>'.$entry['freq'].'</changefreq>';
                    $lines[] = '    <priority>'.$entry['priority'].'</priority>';
                    $lines[] = '  </url>';
                }
            }

            $lines[] = '</urlset>';

            return implode("\n", $lines);
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
