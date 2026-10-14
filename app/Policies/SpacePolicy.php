<?php

namespace App\Policies;

use App\Models\Space;
use App\Models\User;

/**
 * Owner-only authorization for Space actions.
 *
 * Per docs/data-model.md §2: "Each authenticated user can access only their
 * own spaces and associated testimonials." A user is the "owner" of a
 * space if `user_id` matches their id.
 */
class SpacePolicy
{
    public function view(User $user, Space $space): bool
    {
        return $user->id === $space->user_id;
    }

    public function update(User $user, Space $space): bool
    {
        return $user->id === $space->user_id;
    }

    public function delete(User $user, Space $space): bool
    {
        return $user->id === $space->user_id;
    }
}
