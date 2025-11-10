<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarketingController;

Route::middleware('web')->group(function () {
  // Public pages
  Route::get('/',         [MarketingController::class, 'page'])->name('marketing.home')->defaults('pageName', 'marketing/home');
  Route::get('/features', [MarketingController::class, 'page'])->name('marketing.features')->defaults('pageName', 'marketing/features');
  Route::get('/pricing',  [MarketingController::class, 'page'])->name('marketing.pricing')->defaults('pageName', 'marketing/pricing');
  Route::get('/faq',      [MarketingController::class, 'page'])->name('marketing.faq')->defaults('pageName', 'marketing/faq');
  Route::get('/about',    [MarketingController::class, 'page'])->name('marketing.about')->defaults('pageName', 'marketing/about');

  // Contact + Billing pages (public GET, POST protected by CSRF & throttle)

  Route::get('/contact',         [MarketingController::class, 'contactForm'])->name('contact.form');
  Route::post('/contact',        [MarketingController::class, 'submitContact'])
    ->middleware('throttle:6,1') // 6 requests/min per IP
    ->name('contact.submit');
});
