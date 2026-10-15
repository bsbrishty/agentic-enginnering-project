<?php

namespace App\Http\Controllers\Webhook;

use App\Enums\Plan;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionEvent as EventModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription as CashierSubscription;
use Stripe\Event as StripeEvent;
use Stripe\Webhook as StripeWebhook;

/**
 * Stripe webhook handler for the Pro subscription flow.
 *
 * Per docs/data-model.md §7.1 and PRD §9:
 *  - `customer.subscription.created` and `customer.subscription.updated`
 *    flip the user's effective plan to `pro` while the subscription
 *    status is in {active, trialing, past_due} AND (ends_at null OR future).
 *  - `customer.subscription.deleted` flips it back to `free`.
 *
 * Idempotency: every event is recorded in `subscription_events` keyed by
 * `stripe_event_id`. The handler checks this column first; replays are
 * a no-op.
 */
class StripeWebhookController extends Controller
{
    /**
     * Entry point. Verifies the Stripe signature, persists the raw
     * event for the audit log, and dispatches to the type-specific
     * handler.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('stripe.webhook_secret') ?: config('stripe.pro_webhook_secret');

        try {
            // Cashier exposes a typed Webhook event via the SDK signature
            // verification. If verification fails, an exception is thrown.
            $event = $secret
                ? StripeWebhook::constructEvent($payload, $sigHeader, $secret)
                : StripeEvent::constructFrom(json_decode($payload, true));
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'invalid signature'], 400);
        }

        // Idempotency guard. If we've already processed this event, stop.
        if (EventModel::where('stripe_event_id', $event->id)->exists()) {
            return response()->json(['status' => 'duplicate']);
        }

        try {
            DB::transaction(function () use ($event) {
                $subscriptionModel = $this->resolveSubscription($event);
                $userId = $subscriptionModel?->user_id
                    ?? $this->resolveUserIdFromMetadata($event);

                EventModel::create([
                    'user_id'         => $userId,
                    'subscription_id' => $subscriptionModel?->getKey(),
                    'stripe_event_id' => $event->id,
                    'type'            => $event->type,
                    'payload'         => $event->toArray(),
                    'processed_at'    => now(),
                ]);

                $this->dispatch($event);
            });
        } catch (\Throwable $e) {
            Log::error('Stripe webhook processing failed', [
                'event_id' => $event->id,
                'type'     => $event->type,
                'error'    => $e->getMessage(),
            ]);
            return response()->json(['error' => 'processing failed'], 500);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Dispatch a Stripe event to its type-specific handler.
     */
    protected function dispatch(StripeEvent $event): void
    {
        match ($event->type) {
            'customer.subscription.created',
            'customer.subscription.updated'  => $this->handleSubscriptionChange($event),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
            'checkout.session.completed'    => $this->handleCheckoutCompleted($event),
            default                         => null,
        };
    }

    /**
     * On subscription created/updated, set the denormalized `plan` column
     * to `pro` if the subscription is still paying.
     */
    protected function handleSubscriptionChange(StripeEvent $event): void
    {
        $stripeSub = $event->data->object;
        $subscription = CashierSubscription::where('stripe_id', $stripeSub->id)->first();

        if (! $subscription) {
            // Cashier normally creates the local row when the user first
            // checks out, but a race can leave it missing. Create a shell.
            $user = $this->resolveUserFromCustomer($stripeSub->customer ?? null);
            if (! $user) {
                return;
            }
            $subscription = $user->subscriptions()->firstOrNew(['stripe_id' => $stripeSub->id]);
        }

        $subscription->fill([
            'stripe_status' => $stripeSub->status,
            'stripe_price'  => $this->firstItemPriceId($stripeSub),
            'quantity'      => $this->firstItemQuantity($stripeSub),
            'ends_at'       => $stripeSub->cancel_at_period_end
                ? \Carbon\Carbon::createFromTimestamp($stripeSub->current_period_end)
                : null,
            'trial_ends_at' => $stripeSub->trial_end
                ? \Carbon\Carbon::createFromTimestamp($stripeSub->trial_end)
                : null,
        ]);
        $subscription->save();

        $this->refreshPlan($subscription);
    }

    /**
     * On subscription deleted, downgrade to Free.
     */
    protected function handleSubscriptionDeleted(StripeEvent $event): void
    {
        $stripeSub = $event->data->object;
        $subscription = CashierSubscription::where('stripe_id', $stripeSub->id)->first();

        if (! $subscription) {
            return;
        }

        $subscription->update([
            'stripe_status' => $stripeSub->status,
            'ends_at'       => now(),
            'plan'          => Plan::Free->value,
        ]);
    }

    /**
     * On a completed checkout session, no-op. The subscription webhook
     * is the source of truth; this is recorded purely for the audit log.
     */
    protected function handleCheckoutCompleted(StripeEvent $event): void
    {
        // Nothing to do; the subscription.created event will arrive next.
    }

    /**
     * Re-compute the `plan` column on a subscription based on the
     * authoritative data-model §7.1 rule.
     */
    protected function refreshPlan(CashierSubscription $subscription): void
    {
        $activeStatuses = ['active', 'trialing', 'past_due'];

        $isActive = in_array($subscription->stripe_status, $activeStatuses, true)
            && ($subscription->ends_at === null || $subscription->ends_at->isFuture());

        $subscription->plan = $isActive ? Plan::Pro->value : Plan::Free->value;
        $subscription->save();
    }

    /**
     * Resolve the local subscription record for a Stripe event, if any.
     */
    protected function resolveSubscription(StripeEvent $event): ?CashierSubscription
    {
        $obj = $event->data->object;
        if (! isset($obj->id)) {
            return null;
        }

        // For subscription.* events, $obj IS the subscription.
        if (str_starts_with($event->type, 'customer.subscription')) {
            return CashierSubscription::where('stripe_id', $obj->id)->first();
        }

        // For checkout.session.completed, the subscription id is on the
        // session object under .subscription.
        if (isset($obj->subscription)) {
            return CashierSubscription::where('stripe_id', $obj->subscription)->first();
        }

        return null;
    }

    /**
     * Fallback: resolve the user id from the event metadata.
     */
    protected function resolveUserIdFromMetadata(StripeEvent $event): ?int
    {
        $metadata = $event->data->object->metadata ?? null;
        if ($metadata && isset($metadata->app_user_id)) {
            return (int) $metadata->app_user_id;
        }
        return null;
    }

    /**
     * Resolve the local user record from a Stripe customer id.
     *
     * Accepts a nullable value (some events lack a customer field).
     */
    protected function resolveUserFromCustomer(?string $customerId)
    {
        if (! $customerId) {
            return null;
        }
        return \App\Models\User::where('stripe_id', $customerId)->first();
    }

    /**
     * The subscription object's first line item's price id. Handles both
     * Stripe SDK objects (nested objects) and raw JSON arrays.
     */
    protected function firstItemPriceId($stripeSub): ?string
    {
        $items = $stripeSub->items->data ?? $stripeSub->items ?? [];
        $first = $items[0] ?? null;
        if (! $first) {
            return null;
        }
        $price = $first->price ?? null;
        if (is_object($price) && isset($price->id)) {
            return $price->id;
        }
        if (is_array($price) && isset($price['id'])) {
            return $price['id'];
        }
        return null;
    }

    /**
     * The subscription object's first line item's quantity. Handles
     * both Stripe SDK objects and raw JSON arrays.
     */
    protected function firstItemQuantity($stripeSub): ?int
    {
        $items = $stripeSub->items->data ?? $stripeSub->items ?? [];
        $first = $items[0] ?? null;
        if (! $first) {
            return null;
        }
        $q = $first->quantity ?? null;
        return $q !== null ? (int) $q : null;
    }
}