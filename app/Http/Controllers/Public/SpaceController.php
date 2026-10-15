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
     *   - the space is currently accepting submissions (is_active)
     *   - the public_token matches (defense-in-depth)
     *   - the per-space testimonial limit has not been reached
     *   - the DB unique constraint is not violated (race condition)
     *   - the canonical mapping of the free-form `field_values` JSON to
     *     the testimonials row is performed here.
     */
    public function store(StoreTestimonialRequest $request, Space $space): RedirectResponse
    {
        // Honeypot: if the hidden "website" field is filled, treat as bot.
        if ($request->filled('website')) {
            // Silently redirect as if successful to avoid giving the bot feedback.
            return redirect()
                ->to('/c/' . $space->public_slug . '?submitted=1');
        }

        // Space must be active to accept submissions.
        if (! ($space->is_active ?? true)) {
            abort(404);
        }

        // Defense-in-depth: the slug is guessable, so verify the token too.
        if ($request->input('public_token') !== $space->public_token) {
            abort(403, 'Invalid submission token.');
        }

        // Per-space limit (data-model §7.3, PRD §9).
        $plan = $this->plans->resolve($space->owner);
        $current = $space->testimonials()->count();
        if ($current >= $plan->maxTestimonialsPerSpace()) {
            return back()
                ->withErrors(['limit' => 'This space has reached its testimonial limit. Please contact the owner.'])
                ->withInput();
        }

        $data = $request->validated();

        // Free-form values captured by the public form. The `field_values`
        // column on testimonials is the canonical home for the per-form
        // answers; we also map the well-known keys back to the scalar
        // columns the rest of the app reads.
        $fieldValues = $data['field_values'] ?? [];
        $submitterName = (string) ($fieldValues['name'] ?? $data['submitter_name']);
        $submitterEmail = (string) ($fieldValues['email'] ?? $data['submitter_email']);
        $testimonialText = (string) ($fieldValues['testimonial_text'] ?? $data['testimonial_text']);
        $company = $fieldValues['company'] ?? $data['submitter_company'] ?? null;
        $socialLinks = $data['submitter_social_links'] ?? null;
        $profilePhoto = $data['profile_photo_url'] ?? null;

        // Build the row.
        $payload = [
            'submitter_name' => $submitterName,
            'submitter_email' => $submitterEmail,
            'submitter_company' => $company,
            'submitter_social_links' => $socialLinks,
            'testimonial_text' => $testimonialText,
            'rating' => $data['rating'] ?? null,
            'profile_photo_url' => $profilePhoto,
            'field_values' => $fieldValues,
            'consent_state' => $data['consent_state'],
            'submitted_at' => now(),
        ];

        try {
            $space->testimonials()->create($payload);
        } catch (QueryException $e) {
            // SQLSTATE 23000 = integrity constraint violation. The
            // testimonials_space_email_normalized unique constraint is the
            // expected source (production MySQL).
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
