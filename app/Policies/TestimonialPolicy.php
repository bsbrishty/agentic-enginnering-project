<?php

namespace App\Policies;

use App\Models\Testimonial;
use App\Models\User;

/**
 * Owner-only authorization for Testimonial actions.
 *
 * The "owner" of a testimonial is the owner of its space.
 */
class TestimonialPolicy
{
    public function view(User $user, Testimonial $testimonial): bool
    {
        return $user->id === $testimonial->space->user_id;
    }

    public function update(User $user, Testimonial $testimonial): bool
    {
        return $user->id === $testimonial->space->user_id;
    }

    public function delete(User $user, Testimonial $testimonial): bool
    {
        return $user->id === $testimonial->space->user_id;
    }
}
