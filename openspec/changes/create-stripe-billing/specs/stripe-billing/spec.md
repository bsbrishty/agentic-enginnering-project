# Capability: stripe-billing

The outbound Stripe Checkout flow for upgrading a user from Free to Pro. After this change archives, the spec merges into `openspec/specs/stripe-billing/spec.md`.

This change covers only the *purchase* side of Stripe. The webhook handler that confirms the subscription and updates `subscriptions.plan` is a separate change (`create-stripe-webhook-handler`).

## ADDED Requirements

### Requirement: StripeCheckoutService creates a Pro Checkout session

The system SHALL provide `App\Services\StripeCheckoutService::createForUser(User $user): \Stripe\Checkout\Session`.

The service SHALL create a Stripe Checkout session via the SDK with the following parameters:

- `mode = 'subscription'`
- `line_items` containing one item: `{price: config('stripe.pro_price_id'), quantity: 1}`
- `success_url` = `route('dashboard.billing.success')` with `?session_id={CHECKOUT_SESSION_ID}` appended
- `cancel_url` = `route('dashboard.billing.cancel')`
- `client_reference_id` = the user's id (so the webhook can match the session to the user)
- `customer_email` = the user's email
- `metadata` = `['app_user_id' => $user->id, 'app_plan' => 'pro']`

The service SHALL also write a `subscription_events` row with `type = 'checkout.session.created'`, `stripe_event_id = $session->id`, `user_id = $user->id`, and the full session payload in `payload`. This is the audit log for the outbound action; idempotency is enforced by the next change.

The service SHALL throw `RuntimeException` with a clear message if `config('stripe.pro_price_id')` is not set.

#### Scenario: Service creates a session for a free user

- **WHEN** `StripeCheckoutService::createForUser($user)` is called for a free user
- **AND** `STRIPE_PRO_PRICE_ID` is set in `.env`
- **THEN** a `\Stripe\Checkout\Session` is returned with `mode = 'subscription'`
- **AND** the session's `client_reference_id` is the user's id
- **AND** a `subscription_events` row exists with `stripe_event_id` matching the session id and `type = 'checkout.session.created'`

#### Scenario: Service throws when price id is missing

- **WHEN** `StripeCheckoutService::createForUser($user)` is called
- **AND** `config('stripe.pro_price_id')` is null or empty
- **THEN** `RuntimeException` is thrown with a message about the missing price id
- **AND** no Stripe API call is made

### Requirement: BillingController::checkout redirects to Stripe

The `App\Http\Controllers\Dashboard\BillingController` SHALL gain a `checkout(Request $request)` method that:

1. Resolves the user's plan via `PlanResolver::resolve($user)`.
2. If the user is already on `Plan::Pro`, redirects to `route('dashboard.billing.index')` with a flash message.
3. Otherwise, calls `StripeCheckoutService::createForUser($user)` and redirects to the returned session URL.

The route is `GET /dashboard/billing/checkout` (named `dashboard.billing.checkout`), protected by the `auth` middleware (already inherited from the dashboard route group).

#### Scenario: Free user is redirected to Stripe

- **WHEN** an authenticated free user visits `/dashboard/billing/checkout`
- **THEN** a Stripe Checkout session is created
- **AND** the response is a 302 redirect to the session URL (`https://checkout.stripe.com/...`)

#### Scenario: Pro user is not redirected to Stripe

- **WHEN** an authenticated Pro user visits `/dashboard/billing/checkout`
- **THEN** no Stripe API call is made
- **AND** the user is redirected to `/dashboard/billing` with a flash message "You're already on Pro"

#### Scenario: Unauthenticated user is redirected to login

- **WHEN** an unauthenticated visitor visits `/dashboard/billing/checkout`
- **THEN** the response is a 302 redirect to `/login`

### Requirement: BillingController::success shows confirmation without granting access

The `BillingController::success(Request $request)` method SHALL render the `dashboard.billing.success` view with:

- The `session_id` from the query string.
- A flash message encouraging the user to refresh in a few seconds.

The method SHALL NOT update `subscriptions.plan`, SHALL NOT create a `Subscription` row, and SHALL NOT log the user in or out. The actual plan change is performed by the webhook handler in `create-stripe-webhook-handler` when it processes `customer.subscription.created`.

The route is `GET /dashboard/billing/success` (named `dashboard.billing.success`).

#### Scenario: Success view shows confirmation

- **WHEN** Stripe redirects a user to `/dashboard/billing/success?session_id=cs_test_abc`
- **THEN** the response is HTTP 200
- **AND** the view contains a "thank you" message
- **AND** the view contains a link to `/dashboard/billing`
- **AND** the view does NOT claim "you are now on Pro"

#### Scenario: Success view does not mutate the database

- **WHEN** a user visits the success URL
- **THEN** no new `subscriptions` row is created by this code path
- **AND** no existing `subscriptions.plan` value is changed
- **NOTE** the webhook handler is the source of truth for the plan; this method is purely presentational.

### Requirement: BillingController::cancel shows cancellation notice

The `BillingController::cancel(Request $request)` method SHALL render the `dashboard.billing.cancel` view with a "you cancelled" message and a link to `/dashboard/billing`.

The route is `GET /dashboard/billing/cancel` (named `dashboard.billing.cancel`).

#### Scenario: Cancel view shows a back link

- **WHEN** Stripe redirects a user to `/dashboard/billing/cancel`
- **THEN** the response is HTTP 200
- **AND** the view contains a "checkout was cancelled" message
- **AND** the view contains a link to `/dashboard/billing`
- **AND** no database writes occur

### Requirement: Upgrade button uses the checkout route

The `resources/views/dashboard/billing/index.blade.php` view SHALL render the upgrade button as a link to `route('dashboard.billing.checkout')` for free users. The link SHALL NOT be a placeholder or dead URL.

#### Scenario: Free user sees a working upgrade link

- **WHEN** a free user views `/dashboard/billing`
- **THEN** the upgrade button's `href` is `route('dashboard.billing.checkout')` (i.e., `/dashboard/billing/checkout`)

#### Scenario: Pro user does not see the upgrade link

- **WHEN** a Pro user views `/dashboard/billing`
- **THEN** the upgrade button is NOT in the rendered HTML

### Requirement: Stripe config is published and required

The system SHALL publish `config/stripe.php` with a single application-specific value: `pro_price_id => env('STRIPE_PRO_PRICE_ID')`.

The `.env.example` (or equivalent) SHALL document `STRIPE_PRO_PRICE_ID` as a required value for the billing flow to function. The standard `STRIPE_KEY`, `STRIPE_SECRET`, and `STRIPE_WEBHOOK_SECRET` are documented by Cashier.

#### Scenario: Stripe config is loadable

- **WHEN** `php artisan config:show stripe` is run
- **THEN** the output includes `pro_price_id` and the value from the `STRIPE_PRO_PRICE_ID` env var (or null if unset)

#### Scenario: Missing price id produces a clear error

- **WHEN** a user visits `/dashboard/billing/checkout` and `STRIPE_PRO_PRICE_ID` is unset
- **THEN** the request returns HTTP 500
- **AND** the error message indicates the missing Stripe price id
- **AND** no Stripe API call is made

### Requirement: Routes are protected and named

The three new routes SHALL:

- Be inside the `auth` middleware group (already inherited from the `dashboard.` group in `routes/web.php`).
- Have the prefix `dashboard.billing.*` on their names.

The route URLs SHALL be exactly:

- `GET /dashboard/billing/checkout`
- `GET /dashboard/billing/success`
- `GET /dashboard/billing/cancel`

#### Scenario: Route names are correct

- **WHEN** `php artisan route:list --name=dashboard.billing` is run
- **THEN** the output includes `dashboard.billing.index`, `dashboard.billing.checkout`, `dashboard.billing.success`, and `dashboard.billing.cancel`

#### Scenario: All three routes require auth

- **WHEN** an unauthenticated visitor visits any of the three URLs
- **THEN** they are redirected to `/login`
