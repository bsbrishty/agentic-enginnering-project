# Tasks: Create Database Migrations

> **Status:** All migration files have been written into `database/migrations/`.
> Execution of `php artisan migrate` against a live MySQL 8.0 database requires
> the Laravel project to be scaffolded and `cashier:install` to have run, both
> of which are out-of-band steps (see §1). Migration file *creation* tasks
> below are marked complete; the `php artisan migrate` *execution* tasks
> remain unchecked so the implementing engineer verifies them against their
> real environment.

## 1. Prerequisite Setup

- [ ] 1.1 Confirm Laravel 11 project skeleton exists at the repository root.
- [ ] 1.2 Confirm `composer.json` includes `laravel/cashier` as a dependency.
- [ ] 1.3 Run `php artisan cashier:install` to publish Cashier's `subscriptions` and `subscription_items` migrations and the `users`-table Cashier columns.
- [ ] 1.4 Verify `php artisan migrate` succeeds against a clean MySQL 8.0 database with the default Laravel tables plus Cashier's tables.

## 2. `spaces` Migration

- [x] 2.1 Generate the migration: `php artisan make:migration create_spaces_table`.
- [x] 2.2 Implement the schema per `docs/data-model.md` §4.2.
- [ ] 2.3 Run `php artisan migrate` and verify the table with `php artisan db:table spaces`.
- [ ] 2.4 Verify `SHOW INDEX FROM spaces` shows two unique constraints (`public_slug`, `public_token`) and the `user_id` index.

> File written: `database/migrations/2026_10_14_000001_create_spaces_table.php`

## 3. `testimonials` Migration

- [x] 3.1 Generate the migration: `php artisan make:migration create_testimonials_table`.
- [x] 3.2 Implement the `create` schema per `docs/data-model.md` §4.3.
- [x] 3.3 Add the inbox, public-feed, filter, FULLTEXT, and search indexes inside the same `Schema::create` block.
- [x] 3.4 Add the `submitter_email_normalized` STORED generated column via raw SQL `ALTER TABLE`.
- [x] 3.5 Add the dedup unique constraint in a follow-up `Schema::table` block.
- [x] 3.6 Add `use Illuminate\Support\Facades\DB;` to the migration file header.
- [ ] 3.7 Run `php artisan migrate` and verify with `php artisan db:table testimonials`.
- [ ] 3.8 Manually verify the generated column with `SELECT submitter_email, submitter_email_normalized FROM testimonials LIMIT 1;` after inserting a test row with mixed-case email.

> File written: `database/migrations/2026_10_14_000002_create_testimonials_table.php`

## 4. `embed_configs` Migration

- [x] 4.1 Generate the migration: `php artisan make:migration create_embed_configs_table`.
- [x] 4.2 Implement per `docs/data-model.md` §4.4.
- [ ] 4.3 Run `php artisan migrate` and verify the unique index on `public_id`.

> File written: `database/migrations/2026_10_14_000003_create_embed_configs_table.php`

## 5. `subscription_events` Migration

- [x] 5.1 Generate the migration: `php artisan make:migration create_subscription_events_table`.
- [x] 5.2 Implement per `docs/data-model.md` §4.7.
- [ ] 5.3 Run `php artisan migrate` and verify the unique index on `stripe_event_id`.

> File written: `database/migrations/2026_10_14_000004_create_subscription_events_table.php`

## 6. `subscriptions` `plan` Column Migration

- [x] 6.1 Generate: `php artisan make:migration add_plan_to_subscriptions_table --table=subscriptions`.
- [x] 6.2 Add the `plan` ENUM column (`'free'`, `'pro'`) with default `'free'` after `stripe_status`.
- [ ] 6.3 Run `php artisan migrate` and verify the column exists with `php artisan db:table subscriptions`.

> File written: `database/migrations/2026_10_14_000005_add_plan_to_subscriptions_table.php`

## 7. End-to-End Verification

- [ ] 7.1 Run `php artisan migrate:fresh` from a clean state. Must complete without errors.
- [ ] 7.2 Confirm the migration order is correct: Laravel defaults → Cashier → `add_plan_to_subscriptions_table` → `create_spaces_table` → `create_testimonials_table` → `create_embed_configs_table` → `create_subscription_events_table`. Adjust timestamps if needed.
- [ ] 7.3 Verify each invariant from `docs/data-model.md` §2 is enforceable via the schema:
  - **Consent invariant:** `consent_state` ENUM exists with three values; `is_wall_of_love`, `is_hidden` are booleans.
  - **Dedup invariant:** `SHOW INDEX FROM testimonials` shows the `testimonials_space_email_unique` unique constraint.
  - **Cascade rules:** `SHOW CREATE TABLE testimonials` and `... embed_configs` both show `ON DELETE CASCADE` on `space_id`.
  - **Webhook idempotency:** `SHOW INDEX FROM subscription_events` shows unique on `stripe_event_id`.
  - **Plan column:** `subscriptions.plan` ENUM column with `('free','pro')` and default `'free'`.
- [ ] 7.4 Insert a test row into each table directly via SQL to confirm column types and constraints behave as expected (especially the generated column and the FULLTEXT index on `testimonials.testimonial_text`).

## 8. Documentation Cross-Check

- [ ] 8.1 Compare the final migration files against `docs/data-model.md` §4 line-by-line. Any deviation is a bug in either the migration or the doc; do not silently drift.
- [ ] 8.2 Note any deferred decisions encountered during implementation in `docs/data-model.md` §10 ("Open Questions / Deferred Decisions") so they survive into the next change.
