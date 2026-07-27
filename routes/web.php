<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TrackingController;
use App\Http\Middleware\SetStorefrontLocale;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/'.config('storefront.default_locale'));

Route::get('/sitemap.xml', SitemapController::class)->name('storefront.sitemap');
Route::get('/robots.txt', RobotsController::class)->name('storefront.robots');

Route::middleware(SetStorefrontLocale::class)->group(function () {
    Route::get('/{locale}', HomeController::class)->name('storefront.home');
    Route::get('/{locale}/siparis-takibi', TrackingController::class)->name('storefront.tracking');
    Route::get('/{locale}/product/{slug}', ProductController::class)->name('storefront.product');
});
