<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lightweight locale switcher.
 *
 * GET /lang/{locale}
 *
 *   - Validates the requested locale against the application's
 *     `available_locales` config (whitelisted to en + bn).
 *   - Persists the choice in the session so the next request renders
 *     translated strings, and in a 1-year cookie so the choice
 *     survives the session ending.
 *   - Redirects back to the previous URL (`Referer` header or
 *     `dashboard.index` as a safe fallback).
 */
class LocaleController extends Controller
{
    /** Locales this app ships translations for. */
    private const ALLOWED = ['en', 'bn'];

    public function switch(Request $request, string $locale): RedirectResponse
    {
        $locale = strtolower($locale);
        if (! in_array($locale, self::ALLOWED, true)) {
            $locale = config('app.locale', 'en');
        }

        // Persist for the current session.
        $request->session()->put('locale', $locale);

        // Persist across sessions via cookie (1 year).
        cookie()->queue(cookie('app_locale', $locale, 60 * 24 * 365));

        // Apply immediately for the upcoming render.
        app()->setLocale($locale);

        $back = $request->headers->get('referer');
        if ($back && (str_starts_with($back, url('/')) || str_starts_with($back, '/'))) {
            return redirect()->to($back);
        }

        return redirect()->route('dashboard.index');
    }
}