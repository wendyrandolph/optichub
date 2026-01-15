<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
  MarketingController,
  TrialController,
  OnboardingController,
  CheckoutController,
  StripeWebhookController,
  NewsletterController,
  PublicReviewController,
};
use App\Http\Controllers\Client\ProjectConversationController;

/**
 * -------------------------
 * Marketing / Public pages
 * -------------------------
 */

// Home + informational pages
Route::get('/', [MarketingController::class, 'page'])
  ->name('marketing.home')
  ->defaults('pageName', 'marketing/home');

Route::get('/features', [MarketingController::class, 'page'])
  ->name('marketing.features')
  ->defaults('pageName', 'marketing/features');

Route::get('/pricing', [MarketingController::class, 'page'])
  ->name('marketing.pricing')
  ->defaults('pageName', 'marketing/pricing');

Route::get('/faq', [MarketingController::class, 'page'])
  ->name('marketing.faq')
  ->defaults('pageName', 'marketing/faq');

Route::get('/about', [MarketingController::class, 'page'])
  ->name('marketing.about')
  ->defaults('pageName', 'marketing/about');

// Contact form
Route::get('/contact', [MarketingController::class, 'contactForm'])
  ->name('contact.form');

Route::post('/contact', [MarketingController::class, 'submitContact'])
  ->middleware('throttle:6,1')
  ->name('contact.submit');


/**
 * -------------------------
 * Legal (footer links)
 * -------------------------
 */
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/cookies', 'legal.cookies')->name('cookies');
Route::view('/changelog', 'marketing.changelog')->name('changelog');
Route::view('/security', 'marketing.security')->name('security');


/**
 * -------------------------
 * Trial Signup (guest only)
 * -------------------------
 */
Route::middleware('guest')->group(function () {

  // Your existing trial start page:
  // GET: show trial form
  Route::get('/trial',  [TrialController::class, 'showTrialForm'])->name('trial.show');
  Route::post('/trial', [TrialController::class, 'start'])->name('trial.start');


  // Password setup via onboarding email token
  Route::prefix('onboarding')->name('onboarding.')->group(function () {

    Route::get('/welcome', [OnboardingController::class, 'welcome'])
      ->name('welcome');

    Route::get('/brand', [OnboardingController::class, 'brand'])
      ->name('brand');

    Route::post('/brand', [OnboardingController::class, 'storeBrand'])
      ->name('brand.store');

    Route::get('/client', [OnboardingController::class, 'client'])
      ->name('client');

    Route::post('/client', [OnboardingController::class, 'storeClient'])
      ->name('client.store');

    Route::get('/project', [OnboardingController::class, 'project'])
      ->name('project');

    Route::post('/project', [OnboardingController::class, 'storeProject'])
      ->name('project.store');

    Route::get('/finish', [OnboardingController::class, 'finish'])
      ->name('finish');


    // GET onboarding/set-password?token=XYZ
    Route::get('set-password', [OnboardingController::class, 'setupPasswordForm'])
      ->name('setup-password');

    // POST – store password, log in admin user
    Route::post('set-password', [OnboardingController::class, 'handleSetupPassword'])
      ->name('setup-password.store');
  });
});


/**
 * -------------------------
 * Checkout & Welcome
 * -------------------------
 */
Route::post('/checkout', [CheckoutController::class, 'start'])
  ->name('checkout.start');

Route::get('/welcome', [CheckoutController::class, 'welcome'])
  ->name('checkout.welcome');


/**
 * -------------------------
 * Stripe Webhook
 * -------------------------
 */
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
  ->name('stripe.webhook');


/**
 * -------------------------
 * Newsletter subscribe
 * -------------------------
 */
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])
  ->name('newsletter.subscribe');


/**
 * -------------------------
 * Health Check
 * -------------------------
 */
Route::get('/status', function () {
  return response()->json([
    'status'  => 'ok',
    'app'     => config('app.name'),
    'version' => app()->version(),
    'time'    => now()->toIso8601String(),
  ]);
})->name('status');


/**
 * -------------------------
 * Magic Link type of behavior/routes
 * -------------------------
 */
Route::get('/portal/access/{token}', [\App\Http\Controllers\Client\MagicLinkController::class, 'consume'])
  ->name('portal.access');

Route::get('review/{token}', [PublicReviewController::class, 'show'])->name('public.review.show');
Route::post('review/{token}/message', [PublicReviewController::class, 'message'])->name('public.review.message');
Route::post('review/{token}/approve', [PublicReviewController::class, 'approve'])->name('public.review.approve');
Route::get('conversation/{token}', [ProjectConversationController::class, 'showPublic'])
  ->name('conversation.public');
Route::post('conversation/{token}/message', [ProjectConversationController::class, 'publicMessage'])
  ->name('conversation.public.message');
Route::post('conversation/{token}/tasks/{task}/status', [ProjectConversationController::class, 'publicTaskStatus'])
  ->name('conversation.public.task_status');
Route::post('conversation/{token}/messages/{message}/update', [ProjectConversationController::class, 'publicMessageUpdate'])
  ->name('conversation.public.message_update');
Route::delete('conversation/{token}/messages/{message}', [ProjectConversationController::class, 'publicMessageDelete'])
  ->name('conversation.public.message_delete');
Route::get('conversation/{token}/files/{upload}', [ProjectConversationController::class, 'publicFile'])
  ->name('conversation.public.file');
