<?php

namespace App\Services;

use App\Enums\Plan;
use App\Models\SubscriptionEvent;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Cashier\Subscription as CashierSubscription;

/**
 * Local / dev-mode Pro upgrade simulator.
 *
 * Why this exists
 * ---------------
 * PRD §9 calls for a real Stripe Checkout flow. In environments where
 * Stripe is not configured (placeholders, missing keys, or APP_ENV=local
 * without a `stripe listen` tunnel) we still want a one-click path that
 * flips the user to Pro so the rest of the app — plan limits, billing
 * page, embed Pro features — is exercisable end-to-end.
 *
 * This service:
 *   1. Creates a fake `subscriptions` row mirroring what the Stripe
 *      webhook would normally write (data-model §7.1).
 *   2. Records a `subscription_events` audit row marked as the
 *      `checkout.session.completed` and the synthetic
 *      `customer.subscription.created` events.
 *   3. Refuses to run in production with real Stripe keys configured.
 */
class SimulatedUpgradeService
{
    /**
     * True when the simulated upgrade is allowed in the current environment.
     *
     * Refuses if APP_ENV=production. Otherwise allowed even with placeholder
     * Stripe keys, so the local dev experience is unblocked.
     */
    public function isAllowed(): bool
    {
        if (app()->environment('production')) {
            return false;
        }

        // Explicit dev-mock env switch — `STRIPE_MOCK=true` opts in even
        // if APP_ENV is staging.
        if (env('STRIPE_MOCK') === 'true') {
            return true;
        }

        // In local/testing, default to enabled.
        return app()->environment(['local', 'testing'])
            || $this->hasPlaceholderKeys();
    }

    /**
     * Whether the configured Stripe keys are ALL the sample placeholders
     * shipped in `.env.example`. Used to decide between a real checkout
     * session and the simulated upgrade. Requires all three to match so
     * a test that sets only `pro_price_id` to null does not get caught
     * by the "any placeholder matches" logic.
     */
    public function hasPlaceholderKeys(): bool
    {
        $key = (string) config('stripe.key', '');
        $secret = (string) config('stripe.secret', '');
        $price = (string) config('stripe.pro_price_id', '');

        return $key === 'pk_test_sample'
            && $secret === 'sk_test_sample'
            && $price === 'price_pro_sample';
    }

    /**
     * Promote a user to Pro without contacting Stripe. Idempotent: a
     * second call is a no-op (the existing active subscription is kept).
     */
    public function upgrade(User $user): CashierSubscription
    {
        if (! $this->isAllowed()) {
            throw new \RuntimeException(
                'Simulated upgrade is disabled in this environment. Configure real Stripe keys to use the production flow.'
            );
        }

        // Reuse an existing active subscription if one is already present.
        $existing = $user->subscriptions()
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        $now = Carbon::now();
        $stripeId = 'sub_simulated_' . $user->id . '_' . $now->timestamp;

        $subscription = $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => $stripeId,
            'stripe_status' => 'active',
            'stripe_price' => (string) config('stripe.pro_price_id', 'price_pro_sample'),
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
            'plan' => Plan::Pro->value,
        ]);

        SubscriptionEvent::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->getKey(),
            'stripe_event_id' => 'evt_simulated_checkout_' . $stripeId,
            'type' => 'checkout.session.completed',
            'payload' => [
                'simulated' => true,
                'plan' => Plan::Pro->value,
                'price' => (string) config('stripe.pro_price_id'),
                'user_id' => $user->id,
            ],
            'processed_at' => $now,
        ]);

        SubscriptionEvent::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->getKey(),
            'stripe_event_id' => 'evt_simulated_sub_created_' . $stripeId,
            'type' => 'customer.subscription.created',
            'payload' => [
                'simulated' => true,
                'subscription' => [
                    'id' => $stripeId,
                    'status' => 'active',
                    'price' => (string) config('stripe.pro_price_id'),
                    'user_id' => $user->id,
                ],
            ],
            'processed_at' => $now,
        ]);

        return $subscription;
    }

    /**
     * Downgrade the user back to Free (used by the dev "Cancel" button).
     * No-op if the user is already on Free.
     */
    public function cancel(User $user): bool
    {
        if (! $this->isAllowed()) {
            return false;
        }

        $subscription = $user->subscriptions()
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->first();

        if (! $subscription) {
            return false;
        }

        $subscription->update([
            'stripe_status' => 'canceled',
            'ends_at' => Carbon::now(),
            'plan' => Plan::Free->value,
        ]);

        SubscriptionEvent::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->getKey(),
            'stripe_event_id' => 'evt_simulated_sub_deleted_' . $subscription->stripe_id,
            'type' => 'customer.subscription.deleted',
            'payload' => [
                'simulated' => true,
                'subscription_id' => $subscription->stripe_id,
                'user_id' => $user->id,
            ],
            'processed_at' => Carbon::now(),
        ]);

        return true;
    }
}
