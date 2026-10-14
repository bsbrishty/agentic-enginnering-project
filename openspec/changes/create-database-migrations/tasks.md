# Tasks: Create Database Migrations

## 1. Prerequisite Setup

- [ ] 1.1 Confirm Laravel 11 project skeleton exists at the repository root.
- [ ] 1.2 Confirm `composer.json` includes `laravel/cashier` as a dependency.
- [ ] 1.3 Run `php artisan cashier:install` to publish Cashier's `subscriptions` and `subscription_items` migrations and the `users`-table Cashier columns.
- [ ] 1.4 Verify `php artisan migrate` succeeds against a clean MySQL 8.0 database with the default Laravel tables plus Cashier's tables.

## 2. `spaces` Migration

- [ ] 2.1 Generate the migration: `php artisan make:migration create_spaces_table`.
- [ ] 2.2 Implement the schema per `docs/data-model.md` §4.2:
  - `id` (bigIncrements)
  - `user_id` (foreignId, constrained, cascadeOnDelete)
  - `title` (string, required)
  - `subtitle` (string, nullable)
  - `collection_question` (text, required)
  - `theme` (enum: `minimal_light`, `modern_dark`, `warm_friendly`, default `minimal_light`)
  - `rating_enabled` (boolean, default `false`)
  - `fields_config` (json, required)
  - `public_slug` (string, unique)
  - `public_token` (string(64), unique)
  - `timestamps`
  - `index('user_id')` (explicit, even though `foreignId` already indexes)
- [ ] 2.3 Run `php artisan migrate` and verify the table with `php artisan db:table spaces`.
- [ ] 2.4 Verify `SHOW INDEX FROM spaces` shows two unique constraints (`public_slug`, `public_token`) and the `user_id` index.

## 3. `testimonials` Migration

- [ ] 3.1 Generate the migration: `php artisan make:migration create_testimonials_table`.
- [ ] 3.2 Implement the `create` schema per `docs/data-model.md` §4.3:
  - `id` (bigIncrements)
  - `space_id` (foreignId, constrained, cascadeOnDelete)
  - `submitter_name`, `submitter_email` (string)
  - `submitter_company` (string, nullable)
  - `submitter_social_links` (json, nullable)
  - `testimonial_text` (text)
  - `rating` (unsignedTinyInteger, nullable)
  - `profile_photo_url` (string(2048), nullable)
  - `field_values` (json, required)
  - `consent_state` (enum: `granted`, `declined`, `not_asked`)
  - `is_favorite`, `is_wall_of_love`, `is_hidden` (booleans, default false)
  - `submitted_at` (timestamp)
  - `timestamps`
- [ ] 3.3 Add the indexes inside the same `Schema::create` block:
  - `index(['space_id', 'created_at'])` — inbox newest-first
  - `index(['space_id', 'is_wall_of_love', 'is_hidden', 'created_at'], 'testimonials_public_feed_idx')` — public embed covering index
  - `index('is_favorite')`, `index('is_wall_of_love')` — inbox filters
  - `fullText('testimonial_text')` — search
  - `index('submitter_name')`, `index('submitter_email')` — search
- [ ] 3.4 After `Schema::create`, add the `submitter_email_normalized` generated column via raw SQL:
  ```php
  DB::statement('ALTER TABLE testimonials
      ADD COLUMN submitter_email_normalized VARCHAR(255)
      GENERATED ALWAYS AS (LOWER(TRIM(submitter_email))) STORED');
  ```
- [ ] 3.5 Add the dedup unique constraint in a follow-up `Schema::table` block:
  ```php
  $table->unique(['space_id', 'submitter_email_normalized'], 'testimonials_space_email_unique');
  ```
- [ ] 3.6 Add `use Illuminate\Support\Facades\DB;` to the migration file header.
- [ ] 3.7 Run `php artisan migrate` and verify with `php artisan db:table testimonials`.
- [ ] 3.8 Manually verify the generated column with:
  ```sql
  SELECT submitter_email, submitter_email_normalized FROM testimonials LIMIT 1;
  ```
  after inserting a test row with mixed-case email.

## 4. `embed_configs` Migration

- [ ] 4.1 Generate the migration: `php artisan make:migration create_embed_configs_table`.
- [ ] 4.2 Implement per `docs/data-model.md` §4.4:
  - `id` (bigIncrements)
  - `public_id` (uuid, unique)
  - `space_id` (foreignId, constrained, cascadeOnDelete)
  - `layout` (enum: `masonry`, `carousel`)
  - `theme_mode` (enum: `light`, `dark`)
  - `background_color` (string(7))
  - `animation_enabled` (boolean, default `true`)
  - `timestamps`
- [ ] 4.3 Run `php artisan migrate` and verify the unique index on `public_id`.

## 5. `subscription_events` Migration

- [ ] 5.1 Generate the migration: `php artisan make:migration create_subscription_events_table`.
- [ ] 5.2 Implement per `docs/data-model.md` §4.7:
  - `id` (bigIncrements)
  - `user_id` (foreignId, nullable, constrained, cascadeOnDelete)
  - `subscription_id` (foreignId, nullable, constrained to `subscriptions` table, nullOnDelete)
  - `stripe_event_id` (string, unique)
  - `type` (string)
  - `payload` (json)
  - `processed_at` (timestamp, nullable)
  - `timestamps`
  - `index(['user_id', 'created_at'])`
- [ ] 5.3 Run `php artisan migrate` and verify the unique index on `stripe_event_id`.

## 6. `subscriptions` `plan` Column Migration

- [ ] 6.1 Generate: `php artisan make:migration add_plan_to_subscriptions_table --table=subscriptions`.
- [ ] 6.2 Add the `plan` column:
  ```php
  $table->enum('plan', ['free', 'pro'])->default('free')->after('stripe_status');
  ```
- [ ] 6.3 Run `php artisan migrate` and verify the column exists with `php artisan db:table subscriptions`.

## 7. End-to-End Verification

- [ ] 7.1 Run `php artisan migrate:fresh` from a clean state (drops all tables, re-runs all migrations in order). Must complete without errors.
- [ ] 7.2 Confirm the migration order is correct: Laravel defaults → Cashier → `add_plan_to_subscriptions_table` → `create_spaces_table` → `create_testimonials_table` → `create_embed_configs_table` → `create_subscription_events_table`. Adjust timestamps if needed.
- [ ] 7.3 Verify each invariant from `docs/data-model.md` §2 is enforceable via the schema:
  - **Consent invariant:** `consent_state` ENUM exists with three values; `is_wall_of_love`, `is_hidden` are booleans. The application layer must enforce the rule.
  - **Dedup invariant:** `SHOW INDEX FROM testimonials` shows the `testimonials_space_email_unique` unique constraint.
  - **Cascade rules:** `SHOW CREATE TABLE testimonials` and `... embed_configs` both show `ON DELETE CASCADE` on `space_id`.
  - **Webhook idempotency:** `SHOW INDEX FROM subscription_events` shows unique on `stripe_event_id`.
  - **Plan column:** `subscriptions.plan` ENUM column with `('free','pro')` and default `'free'`.
- [ ] 7.4 Insert a test row into each table directly via SQL to confirm column types and constraints behave as expected (especially the generated column and the fulltext index on `testimonials.testimonial_text`).

## 8. Documentation Cross-Check

- [ ] 8.1 Compare the final migration files against `docs/data-model.md` §4 line-by-line. Any deviation is a bug in either the migration or the doc; do not silently drift.
- [ ] 8.2 Note any deferred decisions encountered during implementation in `docs/data-model.md` §10 ("Open Questions / Deferred Decisions") so they survive into the next change.
