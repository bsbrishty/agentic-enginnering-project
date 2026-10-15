<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a public testimonial submission.
 *
 * Per docs/data-model.md §8.2 and PRD §5/§6.
 *
 * The form renders one input per enabled field in `spaces.fields_config`
 * using the field's `key` (e.g. `name`, `email`, `testimonial_text`,
 * `company`, `social_links`, `profile_photo`). The canonical columns on
 * the `testimonials` table are the scalar ones; the free-form
 * `field_values` JSON column captures any non-canonical keys.
 *
 * Notes:
 *   - This request validates the SHAPE of the input only. Cross-cutting rules
 *     live elsewhere:
 *       * Rate limiting & spam protection: route middleware.
 *       * Plan-limit enforcement (testimonials-per-space): controller/policy.
 *       * "rating only valid when spaces.star_ratings_enabled": controller.
 *       * Consent invariant (`consent_state = 'granted'` required for Wall
 *         of Love): data-model §6; the controller must check this before
 *         setting `is_wall_of_love`.
 *   - `space_id` is resolved from the route (`/c/{slug}`), not from the body.
 *   - `submitted_at` is set by the controller to `now()`; not user-assignable.
 *   - Owner-only flags (`is_favorite`, `is_wall_of_love`, `is_hidden`) are
 *     NOT in the rules; the public form cannot promote a testimonial to
 *     the Wall of Love.
 */
class StoreTestimonialRequest extends FormRequest
{
    /**
     * The public submission endpoint is open. Per-space access control is
     * enforced by resolving the space from the route slug and checking
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
            // Required, top-level identification
            'submitter_name' => ['required', 'string', 'max:255'],
            'submitter_email' => ['required', 'email', 'max:255'],

            // Optional identity fields
            'submitter_company' => ['nullable', 'string', 'max:255'],
            'submitter_social_links' => ['nullable'],
            'submitter_social_links.*' => ['nullable', 'url', 'max:2048'],

            // The review itself
            'testimonial_text' => ['required', 'string', 'max:10000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'profile_photo_url' => ['nullable', 'url', 'max:2048'],

            // Honeypot: present in the form but must remain empty.
            // Humans never fill this in; bots typically auto-complete every input.
            // We accept any string here so the request still parses; the
            // controller is what actually treats filled honeypots as bots.
            'website' => ['nullable', 'string', 'max:2048'],

            // Free-form field values captured by the public form's
            // `field_values[name]` / `field_values[email]` / etc. inputs.
            'field_values' => ['nullable', 'array'],

            // Public-consent checkbox (PRD §6). Required.
            'consent_state' => ['required', 'in:granted,declined'],

            // Required security boundary; controller verifies it matches the space.
            'public_token' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'consent_state.required' => 'Please tell us whether we may share your testimonial.',
        ];
    }

    /**
     * Normalize the social links and field_values inputs into the canonical
     * shape before validation rules are applied to derived values.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('social_links') && ! $this->has('submitter_social_links')) {
            $merge['submitter_social_links'] = $this->input('social_links');
        }

        if ($this->has('profile_photo') && ! $this->has('profile_photo_url')) {
            $merge['profile_photo_url'] = $this->input('profile_photo');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
