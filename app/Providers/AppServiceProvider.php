<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Register named rate limiters.
     *
     * - `public-submission` — anti-spam on the public testimonial form.
     *   Allows 5 submissions per minute per IP. The controller also
     *   enforces a honeypot, but a hard cap prevents volume attacks.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('public-submission', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
