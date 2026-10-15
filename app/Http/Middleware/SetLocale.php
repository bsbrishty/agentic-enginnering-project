<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Read the user's preferred locale (cookie > session > Accept-Language >
 * app default) and apply it for the request.
 *
 * Wired into the `web` middleware group from bootstrap/app.php so every
 * rendered view sees the right strings automatically.
 *
 * Safe in tests: when no session is available (e.g. on a bare test
 * request) we silently fall back to the cookie + app default without
 * touching the session store, so we never raise
 * "Session store not set on request."
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = ['en', 'bn'];
        $default = config('app.locale', 'en');

        $locale = null;

        // 1. Session — only if it actually exists on this request.
        try {
            if ($request->hasSession()) {
                $sessionLocale = $request->session()->get('locale');
                if (is_string($sessionLocale) && in_array($sessionLocale, $supported, true)) {
                    $locale = $sessionLocale;
                }
            }
        } catch (\Throwable $e) {
            // Ignore — no session available in this context.
        }

        // 2. Cookie.
        if ($locale === null) {
            $cookieLocale = $request->cookie('app_locale');
            if (is_string($cookieLocale) && in_array($cookieLocale, $supported, true)) {
                $locale = $cookieLocale;
            }
        }

        // 3. Accept-Language header.
        if ($locale === null) {
            $accept = strtolower(substr((string) $request->header('Accept-Language', ''), 0, 2));
            if (in_array($accept, $supported, true)) {
                $locale = $accept;
            }
        }

        // 4. App default.
        if ($locale === null) {
            $locale = $default;
        }

        app()->setLocale($locale);

        return $next($request);
    }
}