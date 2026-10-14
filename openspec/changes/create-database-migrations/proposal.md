# Proposal: Create Database Migrations

## Why

The Testimonial SaaS platform is being built on Laravel 11 + Eloquent + MySQL 8.0 + Laravel Cashier (Stripe). The authoritative data model is defined in `docs/data-model.md` (a 12-section specification covering 5 entities + an audit table, their columns, relationships, indexes, cascade rules, and invariants). Without a migration implementation, no further layer of the application can be built — every feature (auth, spaces, submissions, billing, embeds) depends on the underlying schema existing.

This change implements the migrations only. Models, services, controllers, routes, and the Stripe webhook handler are deliberately out of scope and will be addressed in follow-up changes.

## What

Create Laravel 11 migration files for the four entities owned by this application, plus a single column-add migration for the Cashier-managed `subscriptions` table:

1. **`spaces`** — owner-scoped collection projects with public URL slugs/tokens, configurable form fields, theme, and rating settings.
2. **`testimonials`** — customer submissions linked to a space, with consent, publish/hide flags, dedup-safe email storage, and full-text searchable content.
3. **`embed_configs`** — visual configurations for public embed widgets, addressed by an unguessable `public_id` UUID in the snippet URL.
4. **`subscription_events`** — append-only audit log of Stripe webhook events, used for idempotency.
5. **`subscriptions` extension** — a single migration that adds a `plan` ENUM column (`'free'`, `'pro'`) to Cashier's native `subscriptions` table. The base Cashier table is created by `php artisan cashier:install` and is not modified beyond this column.

The migrations collectively enforce the data-model invariants in §2 of `docs/data-model.md` (consent gating, plan limits, dedup, webhook idempotency, cascade rules).

## Out of Scope

- Eloquent models (separate change: `create-eloquent-models`).
- Form Request validation classes.
- Authentication scaffolding.
- The `Plan` enum and `PlanResolver` service.
- Stripe webhook handler logic (this change creates the *table* the handler will write to; the handler itself comes later).
- Seed data.

## Affected Capabilities

- `database-schema` — new capability spec, added by this change.

## Dependencies

- Laravel 11 application skeleton must exist (separate concern: `composer create-project`).
- `php artisan cashier:install` must be run before this change's migrations execute, so the base `subscriptions` table exists when our `add_plan_to_subscriptions_table` migration runs.
- MySQL 8.0+ is required for the `STORED` generated column on `testimonials.submitter_email_normalized`.

## Risks

- **Generated column syntax differences across MySQL versions.** The `submitter_email_normalized` column uses `GENERATED ALWAYS AS (LOWER(TRIM(submitter_email))) STORED`. MySQL 5.7 supports this but with caveats; 8.0 is the target and the doc's stack assumption.
- **Migration ordering with Cashier.** Cashier publishes its own migrations on `cashier:install`. Our `add_plan_to_subscriptions_table` migration must run *after* Cashier's. Filename timestamps and the manual `php artisan migrate` order handle this in dev; CI must run `cashier:install` (or the equivalent) before the test suite.
- **Index length limits on utf8mb4.** The `submitter_email` and `submitter_name` columns at 255 chars × 4 bytes/char = 1020 bytes, well under InnoDB's 3072-byte limit (MySQL 8.0 default with `innodb_large_prefix` enabled). No action needed, but flagged here for awareness.
