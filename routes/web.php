<?php

use App\Http\Controllers\Dashboard\BillingController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\EmbedConfigController;
use App\Http\Controllers\Dashboard\SpaceController;
use App\Http\Controllers\Dashboard\TestimonialInboxController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Public\EmbedController;
use App\Http\Controllers\Public\SpaceController as PublicSpaceController;
use App\Http\Controllers\Webhook\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/c/{space}', [PublicSpaceController::class, 'show'])
    ->where('space', '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*')
    ->name('public.space.show');

Route::post('/c/{space}', [PublicSpaceController::class, 'store'])
    ->where('space', '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*')
    ->middleware('throttle:public-submission')
    ->name('public.space.store');

Route::get('/embed/{embedConfig:public_id}', [EmbedController::class, 'show'])
    ->name('public.embed.show');

/*
|--------------------------------------------------------------------------
| Locale switcher
|--------------------------------------------------------------------------
*/

Route::get('/lang/{locale}', [LocaleController::class, 'switch'])
    ->where('locale', 'en|bn')
    ->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Webhooks (no auth, no CSRF — Stripe signs the request)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->name('webhooks.stripe')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Authenticated Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');

    // Spaces
    Route::get('spaces', [SpaceController::class, 'index'])->name('spaces.index');
    Route::get('spaces/create', [SpaceController::class, 'create'])->name('spaces.create');
    Route::post('spaces', [SpaceController::class, 'store'])->name('spaces.store');
    Route::get('spaces/{space}', [SpaceController::class, 'show'])->name('spaces.show');
    Route::get('spaces/{space}/edit', [SpaceController::class, 'edit'])->name('spaces.edit');
    Route::patch('spaces/{space}', [SpaceController::class, 'update'])->name('spaces.update');
    Route::delete('spaces/{space}', [SpaceController::class, 'destroy'])->name('spaces.destroy');

    // Inbox
    Route::get('inbox', [TestimonialInboxController::class, 'index'])->name('inbox.index');
    Route::patch('testimonials/{testimonial}', [TestimonialInboxController::class, 'update'])
        ->name('testimonials.update');
    Route::delete('testimonials/{testimonial}', [TestimonialInboxController::class, 'destroy'])
        ->name('testimonials.destroy');

    // Embeds (nested under space)
    Route::get('spaces/{space}/embeds', [EmbedConfigController::class, 'index'])->name('embeds.index');
    Route::get('spaces/{space}/embeds/create', [EmbedConfigController::class, 'create'])->name('embeds.create');
    Route::post('spaces/{space}/embeds', [EmbedConfigController::class, 'store'])->name('embeds.store');
    Route::get('spaces/{space}/embeds/{embedConfig}/edit', [EmbedConfigController::class, 'edit'])->name('embeds.edit');
    Route::patch('spaces/{space}/embeds/{embedConfig}', [EmbedConfigController::class, 'update'])->name('embeds.update');
    Route::delete('spaces/{space}/embeds/{embedConfig}', [EmbedConfigController::class, 'destroy'])->name('embeds.destroy');

    // Billing
    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('billing/simulate', [BillingController::class, 'simulate'])->name('billing.simulate');
    Route::post('billing/cancel-simulated', [BillingController::class, 'cancelSimulated'])->name('billing.cancel-simulated');
    Route::get('billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::get('billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
});

/*
|--------------------------------------------------------------------------
| Home
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard.index')
        : redirect()->route('login');
});
