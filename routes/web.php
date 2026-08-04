<?php

use App\Http\Controllers\BankAccountsController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContentPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MultimediaController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TrackingController;
use App\Http\Middleware\SetStorefrontLocale;
use App\Support\Storefront;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', SitemapController::class)->name('storefront.sitemap');
Route::get('/robots.txt', RobotsController::class)->name('storefront.robots');

/**
 * Vitrin rotaları iki kez tanımlanır:
 *
 * - Varsayılan dil ön eksiz kökte yaşar: /iletisim
 * - Diğer diller ön ekli: /en/iletisim
 *
 * Hangi dilin ön eksiz olduğu panelden gelir; rota tanımı sabit kalsın diye
 * ayrımı SetStorefrontLocale yapar (varsayılan dil ön ekiyle gelen istek kök
 * adrese 301 döner).
 */
$storefront = function (bool $localized) {
    $prefix = $localized ? '/{locale}' : '';
    $name = fn (string $route): string => 'storefront.'.$route.($localized ? '.localized' : '');

    Route::get($prefix ?: '/', HomeController::class)->name($name('home'));
    Route::get($prefix.'/kategori', [CategoryController::class, 'index'])->name($name('categories'));
    Route::get($prefix.'/kategori/{slug}', CategoryController::class)->name($name('category'));
    Route::get($prefix.'/sepet', CartController::class)->name($name('cart'));
    Route::post($prefix.'/siparisler', [CheckoutController::class, 'store'])->name($name('order.store'));
    Route::get($prefix.'/siparis-basarili/{trackingCode}', [CheckoutController::class, 'success'])->name($name('order.success'));
    Route::get($prefix.'/siparis-takibi', TrackingController::class)->name($name('tracking'));
    Route::get($prefix.'/multimedya', MultimediaController::class)->name($name('multimedia'));
    Route::get($prefix.'/iletisim', [ContactController::class, 'show'])->name($name('contact'));
    Route::post($prefix.'/iletisim', [ContactController::class, 'store'])->name($name('contact.store'));
    Route::get($prefix.'/blog', [BlogController::class, 'index'])->name($name('blog.index'));
    Route::get($prefix.'/blog/{slug}', [BlogController::class, 'show'])->name($name('blog.show'));
    Route::get($prefix.'/product/{slug}', ProductController::class)->name($name('product'));
    Route::get($prefix.'/banka-hesaplarimiz', BankAccountsController::class)->name($name('bank-accounts'));

    // Bilgilendirme sayfaları dil kökünün hemen altında yaşar (/kvkk).
    // Sabit storefront rotaları yukarıda tanımlı olduğu için onlar önce eşleşir;
    // bu satır en sonda kalmalı. Ön eksiz sürümde panel ve sistem yolları
    // sayfa slug'ı sanılmasın diye dışarıda bırakılır.
    $page = Route::get($prefix.'/{slug}', ContentPageController::class)->name($name('page'));

    if (! $localized) {
        $page->where('slug', '^(?!admin|storage|livewire|api|build|up$)[^/]+$');
    }
};

Route::middleware(SetStorefrontLocale::class)->group(function () use ($storefront) {
    // Dil ön ekli rotalar önce gelir: /en isteği "en adlı sayfa" sanılmasın.
    Route::whereIn('locale', Storefront::locales())->group(fn () => $storefront(true));

    $storefront(false);
});
