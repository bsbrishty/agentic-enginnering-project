<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTestimonialRequest;
use App\Models\Space;
use App\Services\PlanResolver;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public collection form and submission endpoint.
 *
 * Routes (registered in routes/web.php):
 *   GET  /c/{slug}  -> show
 *   POST /c/{slug}  -> store
 *
 * The slug is the public-facing URL component (data-model §3.2). For
 * defense-in-depth, the controller also verifies the submitted
 * `public_token` against the resolved space; a leaked slug is not
 * sufficient to spam submissions.
 */
class SpaceController extends Controller
{
    public function __construct(private readonly PlanResolver $plans)
    {
    }

    /**
     * Show the public collection form.
     */
    public function show(Request $request, Space $space): View
    {
        return view('public.space', [
            'space' => $space,
            'submitted' => $request->boolean('submitted'),
        ]);
    }

    /**
     * Process a public submission.
     *
     * The StoreTestimonialRequest has already validated the input shape.
     * Here we enforce:
     *   - the public_token matches (defense-in-depth)
     *   - the per-space testimonial limit has not been reached
     *   - the DB unique constraint is not violated (race condition)
     */
    public function store(StoreTestimonialRequest $request, Space $space): RedirectResponse
    {
        // Defense-in-depth: the slug is guessable, so verify the token too.
        if ($request->input('public_token') !== $space->public_token) {
            abort(403, 'Invalid submission token.');
        }

        // Per-space limit (data-model §7.3).
        $plan = $this->plans->resolve($space->owner);
        $current = $space->testimonials()->count();
        if ($current >= $plan->maxTestimonialsPerSpace()) {
            return back()
                ->withErrors(['limit' => 'This space has reached its testimonial limit. Please contact the owner.'])
                ->withInput();
        }

        // Persist. We add submitted_at here; the request does not accept it.
        $payload = $request->validated();
        $payload['submitted_at'] = now();

        try {
            $space->testimonials()->create($payload);
        } catch (QueryException $e) {
            // SQLSTATE 23000 = integrity constraint violation. The
            // testimonials_space_email_normalized unique constraint is the
            // expected source.
            if ($e->getCode() === '23000') {
                return back()
                    ->withErrors(['duplicate' => 'A submission with this email already exists for this space.'])
                    ->withInput();
            }
            throw $e;
        }

        return redirect()
            ->to('/c/' . $space->public_slug . '?submitted=1');
    }
}
