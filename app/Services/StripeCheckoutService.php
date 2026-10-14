<?php

namespace App\Services;

use App\Models\SubscriptionEvent;
use App\Models\User;
use Laravel\Cashier\Cashier;
use RuntimeException;
use Stripe\Checkout\Session as StripeSession;

/**
 * Creates Stripe Checkout sessions for the Pro plan.
 *
 * Per PRD §9 and docs/data-model.md §7.1. The session creation writes a
 * `subscription_events` row for the outbound audit log; the inbound
 * webhook handler (create-stripe-webhook-handler) is what actually
 * confirms the subscription.
 */
class StripeCheckoutService
{
    /**
     * @throws RuntimeException
     */
    public function createForUser(User $user): StripeSession
    {
        $priceId = config('stripe.pro_price_id');
        if (! $priceId) {
            throw new RuntimeException(
                'STRIPE_PRO_PRICE_ID is not set. Configure it in .env before running the billing flow.'
            );
        }

        $session = Cashier::stripe()->checkout->sessions->create([
            'mode' => 'subscription',
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'success_url' => route('dashboard.billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('dashboard.billing.cancel'),
            'client_reference_id' => (string) $user->id,
            'customer_email' => $user->email,
            'metadata' => [
                'app_user_id' => (string) $user->id,
                'app_plan' => 'pro',
            ],
        ]);

        // Audit log: write the outbound session creation to subscription_events.
        // Idempotency for the *inbound* side is the next change's job.
        SubscriptionEvent::create([
            'user_id' => $user->id,
            'subscription_id' => null,
            'stripe_event_id' => $session->id,
            'type' => 'checkout.session.created',
            'payload' => $session->toArray(),
            'processed_at' => now(),
        ]);

        return $session;
    }
}
