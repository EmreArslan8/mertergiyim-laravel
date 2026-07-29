<?php

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
use App\Support\BrandSettings;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/'.BrandSettings::defaultLocale()));

Route::get('/sitemap.xml', SitemapController::class)->name('storefront.sitemap');
Route::get('/robots.txt', RobotsController::class)->name('storefront.robots');

Route::middleware(SetStorefrontLocale::class)->group(function () {
    Route::get('/{locale}', HomeController::class)->name('storefront.home');
    Route::get('/{locale}/kategori', [CategoryController::class, 'index'])->name('storefront.categories');
    Route::get('/{locale}/kategori/{slug}', CategoryController::class)->name('storefront.category');
    Route::get('/{locale}/sepet', CartController::class)->name('storefront.cart');
    Route::post('/{locale}/siparisler', [CheckoutController::class, 'store'])->name('storefront.order.store');
    Route::get('/{locale}/siparis-basarili/{trackingCode}', [CheckoutController::class, 'success'])->name('storefront.order.success');
    Route::get('/{locale}/siparis-takibi', TrackingController::class)->name('storefront.tracking');
    Route::get('/{locale}/multimedya', MultimediaController::class)->name('storefront.multimedia');
    Route::get('/{locale}/iletisim', [ContactController::class, 'show'])->name('storefront.contact');
    Route::post('/{locale}/iletisim', [ContactController::class, 'store'])->name('storefront.contact.store');
    Route::get('/{locale}/blog', [BlogController::class, 'index'])->name('storefront.blog.index');
    Route::get('/{locale}/blog/{slug}', [BlogController::class, 'show'])->name('storefront.blog.show');
    Route::get('/{locale}/product/{slug}', ProductController::class)->name('storefront.product');

    // Bilgilendirme sayfaları dil kökünün hemen altında yaşar (/tr/kvkk).
    // Sabit storefront rotaları yukarıda tanımlı olduğu için onlar önce eşleşir;
    // bu satır en sonda kalmalı.
    Route::get('/{locale}/{slug}', ContentPageController::class)->name('storefront.page');
});
