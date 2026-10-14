<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EmbedConfig;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public embed widget iframe.
 *
 * Route (registered in routes/web.php):
 *   GET /embed/{public_id}  -> show
 *
 * No authentication. The view is loaded inside an iframe on third-party
 * sites. The eligible-testimonials query is the *read path* enforcement
 * of the consent invariant (data-model §6).
 */
class EmbedController extends Controller
{
    /**
     * Render the embed widget.
     */
    public function show(Request $request, EmbedConfig $embedConfig): View
    {
        $space = $embedConfig->space;

        // Single source of truth for the consent invariant at the read path.
        $testimonials = $space->testimonials()
            ->where('is_wall_of_love', true)
            ->where('is_hidden', false)
            ->where('consent_state', 'granted')
            ->orderByDesc('created_at')
            ->get();

        // Public, short-lived caching. Third-party iframes may load this on
        // every page view; cap how often they hit our DB.
        // The header is set in the view via response()->header() at the
        // controller level, but for a View return we set it here on the
        // response object after rendering. See the closure below.
        return response()
            ->view('public.embed', [
                'embedConfig' => $embedConfig,
                'space' => $space,
                'testimonials' => $testimonials,
            ])
            ->header('Cache-Control', 'public, max-age=60');
    }
}
