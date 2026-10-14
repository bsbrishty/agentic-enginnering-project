# Proposal: Create Stripe Billing

## Why

The Testimonial SaaS has two plans (Free and Pro; PRD §9) but until now, the upgrade button on `/dashboard/billing` links to a 404 placeholder. The plan-limit enforcement at the application layer (`PlanResolver` from `create-public-routes`) and at the database layer (the columns and ENUMs in `create-database-migrations`) is already in place. What is missing is the *purchase* flow: a Stripe Checkout session that the user is redirected to, and a return path that lands them back on the billing page once the payment succeeds.

The webhooks that confirm the subscription and synchronize state (PRD §9 "Process Stripe webhooks to synchronize subscription status") are the next change (`create-stripe-webhook-handler`); this change wires only the *outbound* side of the Stripe integration. The two together form a complete loop: a user clicks "Upgrade", this change creates a Checkout session and redirects; the user pays on Stripe; the webhook (next change) updates the `subscriptions` row and the `plan` column.

## What

This change adds the Stripe Checkout outbound flow to the existing `BillingController`:

### Routes

- `GET /dashboard/billing/checkout` → `BillingController@checkout` (named `dashboard.billing.checkout`).
- `GET /dashboard/billing/success` → `BillingController@success` (named `dashboard.billing.success`).
- `GET /dashboard/billing/cancel` → `BillingController@cancel` (named `dashboard.billing.cancel`).

### Controller additions

`App\Http\Controllers\Dashboard\BillingController` gains three new methods:

- `checkout(Request $request)` — creates a Stripe Checkout session for the Pro plan, persists a `subscription_events` row with the Stripe session id (`type = 'checkout.session.created'`) for traceability, and redirects to the session URL. Refuses to redirect for users who are already on Pro.
- `success(Request $request)` — the success return URL. Renders a "thanks for upgrading" page. **Does NOT grant Pro access based on visiting this URL.** The actual `subscriptions.plan` change is performed by the webhook (next change) when it processes the `customer.subscription.created` event. This view shows a polling-friendly "we're confirming your subscription" message until the webhook has run.
- `cancel(Request $request)` — the cancellation return URL. Renders a "you cancelled" page with a link back to `/dashboard/billing`.

### Configuration

- `config/stripe.php` — published with a single key: `STRIPE_PRO_PRICE_ID` (the Stripe Price id for the Pro subscription). The `STRIPE_KEY`, `STRIPE_SECRET`, and `STRIPE_WEBHOOK_SECRET` env vars are read directly by Cashier and the webhook handler; this config file is for application-specific Stripe ids only.
- A new env var: `STRIPE_PRO_PRICE_ID` in `.env.example`.

### Service

- `App\Services\StripeCheckoutService` — encapsulates the session creation, the idempotency key, and the success/cancel URL construction. The controller delegates to it.

### View

- `resources/views/dashboard/billing/success.blade.php` — "thanks" page with a "back to billing" link.
- `resources/views/dashboard/billing/cancel.blade.php` — "cancelled" page.

The existing `dashboard/billing/index.blade.php` from `create-auth-and-dashboard` is updated to point its upgrade button at `route('dashboard.billing.checkout')` instead of a placeholder.

## Out of Scope

- The Stripe webhook handler — `create-stripe-webhook-handler`. This change creates the *session*; the next change consumes the *events* that confirm the session's subscription.
- The Stripe customer portal (for managing/cancelling existing subscriptions). Deferred to a later change.
- Multiple paid tiers, team billing, proration logic. The PRD explicitly defers these (PRD §15 "Scope constraint").
- Coupon codes or promotional pricing.
- Localization of the Stripe-hosted checkout page.
- The webhook signature verification — that's the next change.
- The cron that periodically syncs subscription state from Stripe. Cashier's `Billable::subscription()->syncStripeStatus()` exists; this change does not add a scheduled call to it.

## Affected Capabilities

- `stripe-billing` — new capability spec, added by this change.

## Dependencies

- `composer require laravel/cashier` (required at Laravel scaffold time)
- `create-database-migrations` (applied) — the `subscriptions` and `subscription_events` tables exist
- `create-eloquent-models` (applied) — the `User` model uses the `Billable` trait
- `create-auth-and-dashboard` (applied) — the `BillingController` and the billing view are extended, not replaced
- The `STRIPE_*` env vars must be set in `.env` (CASHIER's defaults)

## Risks

- **Race between Checkout success and webhook delivery.** A user who completes payment is redirected to `/dashboard/billing/success` *before* the webhook fires. The success view must NOT claim "you're on Pro now"; it should say "we're confirming your subscription, refresh in a few seconds." The actual plan change happens via the webhook in the next change.
- **Idempotency of session creation.** A user clicking the upgrade button twice in quick succession would create two Stripe sessions. Mitigated by checking the user's current plan before redirecting (no checkout for pro users) and by relying on the `checkout.session.created` row in `subscription_events` as an audit log. A stricter approach (e.g., debouncing on the user) is a v2 refinement.
- **Price id must exist in Stripe.** If `STRIPE_PRO_PRICE_ID` is not set, the checkout creation will throw. The controller surfaces a clear error in that case.
- **The Cashier-provided `checkout()` method on `Billable` is *not* used.** Cashier's default is fine for simple cases, but it does not log the session to `subscription_events` for our custom audit trail. We use Stripe's PHP SDK directly via Cashier's underlying `Stripe::checkout()->sessions->create()` to maintain control of the `client_reference_id` and metadata.
- **Success and cancel URLs include the user's id and the Stripe session id as query params.** This is for the success view to poll the local DB for the subscription state once the webhook has run. The view does not include any sensitive data.
- **The Cashier `STRIPE_WEBHOOK_SECRET` env var is required for the *next* change, not this one.** This change does not register a webhook endpoint.
