<?php

namespace App\Http\Controllers;

use App\Services\DictionaryService;
use App\Services\StorefrontRepository;
use App\Support\Storefront;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * app/[locale]/siparis-takibi karşılığı. Sorgu sunucuda çalışır (?q=...).
 */
class TrackingController extends Controller
{
    public function __construct(private StorefrontRepository $repository) {}

    public function __invoke(Request $request): View
    {
        $locale = app()->getLocale();
        $query = trim((string) $request->query('q', ''));
        $order = null;
        $error = '';

        if ($request->has('q')) {
            $messages = $this->dictionaryMessages();

            if ($query === '') {
                $error = $messages['errorEmpty'];
            } else {
                $order = $this->repository->trackOrder($query);
                $error = $order ? '' : $messages['errorNotFound'];
            }
        }

        return view('storefront.tracking', [
            'query' => $query,
            'order' => $order,
            'error' => $error,
            'canonicalPath' => Storefront::localePath($locale, '/siparis-takibi'),
            'alternatePath' => fn (string $code) => Storefront::localePath($code, '/siparis-takibi'),
        ]);
    }

    private function dictionaryMessages(): array
    {
        return app(DictionaryService::class)->all(app()->getLocale())['tracking'] ?? [];
    }
}
