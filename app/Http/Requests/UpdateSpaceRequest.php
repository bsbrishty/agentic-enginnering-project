<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a request to update an existing Space.
 *
 * Per docs/data-model.md §8.1, with `sometimes` on every rule to support
 * partial updates.
 *
 * IMMUTABLE FIELDS (NOT included in `rules()`):
 *   - `public_slug`     — Round 1 grill decision: URLs are not rotated.
 *   - `public_token`    — Security boundary; rotating it would silently
 *                         break any in-flight submissions to the old token.
 *   - `user_id`         — Ownership is not transferable.
 *
 * Controllers MUST NOT assign these fields from `$request->validated()` or
 * `$request->all()`. The model's `creating` hook is also inactive on update,
 * so even if the controller assigns the value, the existing column value is
 * preserved. This class makes that constraint explicit at the validation
 * layer.
 */
class UpdateSpaceRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'subtitle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'collection_question' => ['sometimes', 'required', 'string', 'max:5000'],
            'theme' => ['sometimes', 'required', 'in:minimal_light,modern_dark,warm_friendly'],
            'rating_enabled' => ['sometimes', 'required', 'boolean'],
            'fields_config' => ['sometimes', 'required', 'array'],
        ];
    }
}
