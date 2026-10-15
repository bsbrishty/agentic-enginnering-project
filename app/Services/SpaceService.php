<?php

namespace App\Services;

use App\Enums\Plan;
use App\Exceptions\SpaceLimitReachedException;
use App\Models\Space;
use App\Models\User;

/**
 * Encapsulates the dashboard-side space lifecycle.
 *
 * - `create()` enforces the per-plan space limit.
 * - `update()` is a thin pass-through; the form request handles validation.
 *
 * Per docs/data-model.md §7.2 and the PRD §9.
 */
class SpaceService
{
    public function __construct(private readonly PlanResolver $plans)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws SpaceLimitReachedException
     */
    public function create(User $owner, array $data): Space
    {
        $plan = $this->plans->resolve($owner);
        $current = $owner->spaces()->count();

        if ($current >= $plan->maxSpaces()) {
            throw new SpaceLimitReachedException($owner, $current, $plan->maxSpaces());
        }

        $data['user_id'] = $owner->id;
        $data['fields_config'] = $this->decodeFieldsConfig($data['fields_config'] ?? null);

        // The Space model's `creating` hook auto-fills public_slug and
        // public_token. We do not pass them in $data.
        unset($data['public_slug'], $data['public_token']);

        // Boolean checkboxes: missing == unchecked. Mirror the legacy
        // `rating_enabled` from `star_ratings_enabled` when only one is set.
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['star_ratings_enabled'] = (bool) ($data['star_ratings_enabled'] ?? $data['rating_enabled'] ?? false);
        $data['rating_enabled'] = $data['star_ratings_enabled'];

        return $owner->spaces()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Space $space, array $data): Space
    {
        // Hard guarantee: even if the caller passes these, they are not
        // touched. UpdateSpaceRequest already excludes them from the rules.
        unset($data['public_slug'], $data['public_token'], $data['user_id']);

        if (isset($data['fields_config'])) {
            $data['fields_config'] = $this->decodeFieldsConfig($data['fields_config']);
        }

        // Mirror the legacy rating_enabled with star_ratings_enabled.
        if (array_key_exists('star_ratings_enabled', $data) && ! array_key_exists('rating_enabled', $data)) {
            $data['rating_enabled'] = (bool) $data['star_ratings_enabled'];
        } elseif (array_key_exists('rating_enabled', $data) && ! array_key_exists('star_ratings_enabled', $data)) {
            $data['star_ratings_enabled'] = (bool) $data['rating_enabled'];
        }

        $space->update($data);

        return $space;
    }

    /**
     * Decode the JSON-string `fields_config` from a form submission into
     * the array shape the model's `array` cast expects.
     *
     * @return array<int, array<string, mixed>>
     */
    private function decodeFieldsConfig(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }
}
