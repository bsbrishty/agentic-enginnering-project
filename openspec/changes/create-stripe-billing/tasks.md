# Tasks: Create Stripe Billing

> **Status:** All routes, controller methods, services, and views have been written.
> The "verify" tasks require a real Laravel environment + Stripe test keys to execute and are left unchecked for the implementing engineer to run.

## 1. Prerequisites

- [ ] 1.1 Confirm Laravel 11 project skeleton exists.
- [ ] 1.2 Confirm `composer.json` includes `laravel/cashier`.
- [ ] 1.3 Confirm `database/migrations/` is in place, especially `add_plan_to_subscriptions_table` and the `subscription_events` table.
- [ ] 1.4 Confirm `App\Models\User` uses the `Billable` trait.
- [ ] 1.5 Confirm `STRIPE_KEY`, `STRIPE_SECRET`, and `STRIPE_WEBHOOK_SECRET` are in `.env`. (The webhook secret is for the next change; the first two are required for this one.)
- [ ] 1.6 Confirm `php artisan migrate:fresh` runs cleanly.

## 2. Configuration

- [x] 2.1 Create `config/stripe.php` with a single key: `'pro_price_id' => env('STRIPE_PRO_PRICE_ID')`.
- [ ] 2.2 Document `STRIPE_PRO_PRICE_ID` in the project's `.env.example`.

> File written: `config/stripe.php`

## 3. `StripeCheckoutService`

- [x] 3.1 Create `App\Services\StripeCheckoutService` with `createForUser(User $user): \Stripe\Checkout\Session`.
- [x] 3.2 The service uses `Stripe::checkout()->sessions->create([...])` (Cashier exposes the Stripe SDK via this facade).
- [x] 3.3 The session is configured with:
  - `mode = 'subscription'`
  - `line_items = [[price: config('stripe.pro_price_id'), quantity: 1]]`
  - `success_url` and `cancel_url` pointing at `route('dashboard.billing.success')` and `route('dashboard.billing.cancel')` with the session id appended.
  - `client_reference_id = $user->id` so the webhook (next change) can match the session back to a user.
  - `customer_email = $user->email` (Cashier will create the Stripe customer on first webhook; using email is simpler for MVP).
  - `metadata = ['app_user_id' => $user->id, 'app_plan' => 'pro']`.
- [x] 3.4 The service writes a `subscription_events` row with `type = 'checkout.session.created'`, `stripe_event_id = $session->id`, `user_id = $user->id`, and the full session payload.
- [x] 3.5 The service throws `RuntimeException` if `stripe.pro_price_id` is not configured.

> File written: `app/Services/StripeCheckoutService.php`

## 4. `BillingController` additions

- [x] 4.1 Add `checkout(Request $request)` method to the existing `BillingController`:
  - Resolve the current plan via `PlanResolver`.
  - If the user is already on `Plan::Pro`, redirect to `/dashboard/billing` with a flash "you're already on Pro".
  - Otherwise, call `StripeCheckoutService::createForUser($user)` and redirect to the returned session URL.
- [x] 4.2 Add `success(Request $request)` method:
  - Read `session_id` from the query string.
  - Pass a "we're confirming" message and the session id to the success view.
  - **Do NOT mutate the `subscriptions.plan` here** — the webhook (next change) is the source of truth.
- [x] 4.3 Add `cancel(Request $request)` method:
  - Render the cancel view with a "back to billing" link.

> File modified: `app/Http/Controllers/Dashboard/BillingController.php`

## 5. Routes

- [x] 5.1 Add three routes to `routes/web.php` inside the `auth` + `dashboard.` group:
  - `GET /dashboard/billing/checkout` → `BillingController@checkout` (named `dashboard.billing.checkout`)
  - `GET /dashboard/billing/success` → `BillingController@success` (named `dashboard.billing.success`)
  - `GET /dashboard/billing/cancel` → `BillingController@cancel` (named `dashboard.billing.cancel`)

> File modified: `routes/web.php`

## 6. Views

- [x] 6.1 Create `resources/views/dashboard/billing/success.blade.php` — "Thanks for upgrading" message with a "back to billing" link. Includes a small inline meta-refresh to encourage the user to refresh and see the updated plan.
- [x] 6.2 Create `resources/views/dashboard/billing/cancel.blade.php` — "You cancelled" message with a "back to billing" link.
- [x] 6.3 Update the existing `resources/views/dashboard/billing/index.blade.php` to use `route('dashboard.billing.checkout')` for the upgrade button instead of a placeholder URL.

> Files written: `resources/views/dashboard/billing/success.blade.php`, `resources/views/dashboard/billing/cancel.blade.php`
> File modified: `resources/views/dashboard/billing/index.blade.php`

## 7. End-to-End Verification (with Stripe test mode)

- [ ] 7.1 Run `php artisan migrate:fresh` against a clean DB.
- [ ] 7.2 In `tinker`, create a user and log in.
- [ ] 7.3 Visit `/dashboard/billing`. The upgrade button is visible and links to `route('dashboard.billing.checkout')`.
- [ ] 7.4 Click the upgrade button. You are redirected to a `checkout.stripe.com/...` URL.
- [ ] 7.5 Use Stripe's test card `4242 4242 4242 4242` with any future expiry and any CVC. Complete the test payment.
- [ ] 7.6 You are redirected to `/dashboard/billing/success?session_id={cs_test_...}`. The success view renders. **The user is NOT yet on Pro** (the webhook hasn't fired yet in this change; it comes next).
- [ ] 7.7 Visit `/dashboard/billing` again. The user is still on Free. (Without the webhook handler, no `subscriptions` row was created. This is correct for this change; the webhook handler change will fix this.)
- [ ] 7.8 Visit `/dashboard/inbox` and the dashboard — both still show the Free plan. Same: correct without the webhook.
- [ ] 7.9 Repeat the test but click "Back" on Stripe's hosted checkout. You are redirected to `/dashboard/billing/cancel`. The cancel view renders.

## 8. Documentation Cross-Check

- [ ] 8.1 Compare the controller and routes against PRD §9 ("Subscription and Billing") and data-model §7.1 ("Plan derivation") line-by-line. Any deviation is a bug.
- [ ] 8.2 Note any deferred decisions in `docs/data-model.md` §10 (e.g., the customer portal for managing existing subscriptions is deferred).
