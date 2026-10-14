<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a request to create a new Space.
 *
 * Per docs/data-model.md §8.1.
 *
 * Notes:
 *   - `public_slug` and `public_token` are NOT in the rules; the Space model's
 *     `creating` hook generates them (see eloquent-models spec).
 *   - `user_id` is set by the controller from the authenticated user.
 *   - The `fields_config` shape is validated as `array` only; per-field
 *     structural validation is a deferred decision (§10 of the data-model doc).
 */
class StoreSpaceRequest extends FormRequest
{
    /**
     * Access control is handled by route middleware (e.g. `auth`, `verified`).
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
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'collection_question' => ['required', 'string', 'max:5000'],
            'theme' => ['required', 'in:minimal_light,modern_dark,warm_friendly'],
            'rating_enabled' => ['required', 'boolean'],
            'fields_config' => ['required', 'array'],
        ];
    }
}
