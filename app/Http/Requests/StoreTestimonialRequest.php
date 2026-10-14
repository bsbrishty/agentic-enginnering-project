<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a public testimonial submission.
 *
 * Per docs/data-model.md §8.2.
 *
 * Notes:
 *   - This request validates the SHAPE of the input only. Cross-cutting rules
 *     live elsewhere:
 *       * Rate limiting & spam protection: route middleware.
 *       * Plan-limit enforcement (testimonials-per-space): controller/policy.
 *       * "rating only valid when spaces.rating_enabled": controller/policy.
 *       * Consent invariant (`consent_state = 'granted'` required for Wall
 *         of Love): data-model §6; the controller must check this before
 *         setting `is_wall_of_love`.
 *   - `space_id` is resolved from the route (`/c/{token}`), not from the body.
 *   - `submitted_at` is set by the controller to `now()`; not user-assignable.
 *   - Owner-only flags (`is_favorite`, `is_wall_of_love`, `is_hidden`) are
 *     NOT in the rules; the public form cannot promote a testimonial to
 *     the Wall of Love.
 */
class StoreTestimonialRequest extends FormRequest
{
    /**
     * The public submission endpoint is open. Per-space access control is
     * enforced by resolving the space from the route token and checking
     * its state (not deleted, accepting submissions, etc.).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'submitter_name' => ['required', 'string', 'max:255'],
            'submitter_email' => ['required', 'email', 'max:255'],
            'submitter_company' => ['nullable', 'string', 'max:255'],

            'submitter_social_links' => ['nullable', 'array'],
            'submitter_social_links.*' => ['nullable', 'url', 'max:2048'],

            'testimonial_text' => ['required', 'string', 'max:10000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],

            'profile_photo_url' => ['nullable', 'url', 'max:2048'],

            'field_values' => ['required', 'array'],

            'consent_state' => ['required', 'in:granted,declined,not_asked'],
        ];
    }
}
