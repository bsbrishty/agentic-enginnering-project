<?php

namespace App\Services;

use App\Exceptions\ConsentViolationException;
use App\Models\Testimonial;

/**
 * Application-level guard for the consent invariant (docs/data-model.md §6,
 * PRD §7).
 *
 * A testimonial is eligible for the Wall of Love (and therefore for any
 * public embed) only if its `consent_state` is `'granted'`. Both
 * `'declined'` and `'not_asked'` block publication.
 *
 * The DB allows any value of `is_wall_of_love` regardless of `consent_state`;
 * the embed widget's read query filters on `consent_state = 'granted'` as a
 * safety net, but the application-level check via this guard is the
 * authoritative gate.
 */
class ConsentGuard
{
    /**
     * Throws if the testimonial cannot be published.
     */
    public function assertCanPublish(Testimonial $testimonial): void
    {
        if ($testimonial->consent_state !== 'granted') {
            throw new ConsentViolationException($testimonial);
        }
    }

    /**
     * Returns true if the testimonial is currently publishable, false
     * otherwise. Does not throw.
     */
    public function canPublish(Testimonial $testimonial): bool
    {
        return $testimonial->consent_state === 'granted';
    }
}
