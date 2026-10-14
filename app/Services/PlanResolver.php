<?php

namespace App\Services;

use App\Enums\Plan;
use App\Models\User;

/**
 * Resolves a user's effective plan from their Cashier subscription state.
 *
 * Per docs/data-model.md §7.1:
 *   - "Pro" if any `subscriptions` row has stripe_status in
 *     (active, trialing, past_due) AND (ends_at IS NULL OR ends_at > now()).
 *   - "Free" otherwise.
 *
 * The denormalized `subscriptions.plan` column is a cache of this state, but
 * the authoritative derivation is this method.
 */
class PlanResolver
{
    /** Stripe statuses that count as "still paying". */
    private const ACTIVE_STATUSES = ['active', 'trialing', 'past_due'];

    public function resolve(User $user): Plan
    {
        $hasPro = $user->subscriptions()
            ->whereIn('stripe_status', self::ACTIVE_STATUSES)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->exists();

        return $hasPro ? Plan::Pro : Plan::Free;
    }
}
