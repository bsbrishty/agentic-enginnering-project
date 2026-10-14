# Proposal: Create Eloquent Models

## Why

The `create-database-migrations` change produced the schema; without Eloquent models the rest of the application has no way to read or write the database. Every subsequent feature (auth scaffolding, the dashboard, the public collection form, the embed widget, the Stripe webhook handler) is built on top of these models. The data-model spec in `docs/data-model.md` §11 names the exact relationship method contract the rest of the app will call; this change implements that contract.

## What

Create or update five Eloquent model classes under `app/Models/`:

1. **`User`** (update) — add the `Billable` trait from `Laravel\Cashier`, expose `spaces()` and `subscriptionEvents()` relationships, keep all existing auth-related fields.
2. **`Space`** (new) — fillable, casts (including JSON columns and enums), relationships (`owner()`, `testimonials()`, `embedConfigs()`), and a `booted()` hook that generates `public_slug` and `public_token` on create.
3. **`Testimonial`** (new) — fillable, casts, relationship (`space()`).
4. **`EmbedConfig`** (new) — fillable, casts, relationship (`space()`), and a `booted()` hook that generates a UUID v4 `public_id` on create.
5. **`SubscriptionEvent`** (new) — fillable, casts, relationships (`user()`, `subscription()`).

The relationship method names and column names follow `docs/data-model.md` §11 verbatim, including the `Space::owner()` alias of the `belongsTo User` relationship (the underlying FK column stays `user_id`).

## Out of Scope

- Form Request validation classes (`create-form-requests`, separate change).
- The `Plan` enum and `PlanResolver` service.
- The Stripe webhook handler logic (this change provides the *models* the handler will use; the handler comes in `create-stripe-webhook-handler`).
- Policies, scopes, and access control.
- Factories and seeders.
- Tests (the spec scenarios in `specs/eloquent-models/spec.md` describe the contract; PHPUnit feature tests come in a dedicated `add-model-tests` change).

## Affected Capabilities

- `eloquent-models` — new capability spec, added by this change.

## Dependencies

- `create-database-migrations` (applied) — schema must exist before models are meaningful.
- `composer require laravel/cashier` (applied at Laravel scaffold time) — the `Billable` trait and `subscriptions()` method must be available.

## Risks

- **`booted()` hook ordering.** Laravel's `creating` event fires before the insert, so values assigned there are persisted. If a future change adds another `booted()` hook to a subclass, ordering must be preserved. Documented in the spec.
- **Mass assignment.** `$fillable` is set to the full set of writable columns for each model. `consent_state` is intentionally fillable because the public submission form writes it; a future change may want to narrow this via `$guarded` or Form Requests.
- **JSON column cast caveats.** Casting `fields_config` and `field_values` to `array` works in MySQL 8.0 but the round-trip is not guaranteed to preserve key order. The data-model spec calls for arrays of objects in `fields_config` to preserve *render order*; in practice the application layer should treat the array as ordered without relying on the DB layer to guarantee it.
- **`Billable` trait vs. existing auth model.** If the project's existing `User` model already declares a `subscriptions()` relationship, the `Billable` trait's method takes precedence. Verified in §3.1 of the data-model spec.
