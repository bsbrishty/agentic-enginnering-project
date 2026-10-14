<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a request to create a new EmbedConfig.
 *
 * Per docs/data-model.md §8.3.
 *
 * Notes:
 *   - `public_id` is NOT in the rules; the EmbedConfig model's `creating`
 *     hook generates a UUID v4 (see eloquent-models spec).
 *   - `space_id` is resolved from the route; not user-assignable.
 */
class StoreEmbedConfigRequest extends FormRequest
{
    /**
     * Access control is handled by route middleware and the Space policy.
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
            'layout' => ['required', 'in:masonry,carousel'],
            'theme_mode' => ['required', 'in:light,dark'],
            'background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'animation_enabled' => ['required', 'boolean'],
        ];
    }
}
