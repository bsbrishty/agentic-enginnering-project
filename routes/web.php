<?php

use App\Http\Controllers\Public\EmbedController;
use App\Http\Controllers\Public\SpaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| Unauthenticated surface for the Testimonial SaaS:
|
|   GET  /c/{slug}        Public collection form
|   POST /c/{slug}        Public submission endpoint
|   GET  /embed/{public_id}  Public embed widget iframe
|
| The slug route param is constrained to slug-shaped strings. The
| `public_id` route param uses Eloquent's implicit binding via
| `getRouteKeyName()` on the EmbedConfig model.
|
| No `auth` middleware. The submission route uses
| `throttle:public-submission` to limit request rate; the rate itself
| is configured in a service provider (a future change).
|
| The dashboard, billing, and authentication routes are added in
| separate changes and will be merged into this file or a sibling
| routes file.
*/

Route::get('/c/{slug:slug}', [SpaceController::class, 'show'])
    ->name('public.space.show');

Route::post('/c/{slug:slug}', [SpaceController::class, 'store'])
    ->middleware('throttle:public-submission')
    ->name('public.space.store');

Route::get('/embed/{public_id}', [EmbedController::class, 'show'])
    ->name('public.embed.show');
