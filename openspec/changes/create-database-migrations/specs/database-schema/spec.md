# Capability: database-schema

The persistent schema for the Testimonial SaaS platform. This spec is the *delta* for the `create-database-migrations` change; once archived, it merges into `openspec/specs/database-schema/spec.md` as the canonical schema contract.

Source of truth: `docs/data-model.md`. Migrations are the implementation of that spec; this delta spec is the testable contract.

## ADDED Requirements

### Requirement: Spaces table

The system SHALL provide a `spaces` table that stores owner-scoped testimonial collection projects.

The table SHALL contain the columns: `id` (bigIncrements primary key), `user_id` (FK to `users.id`, ON DELETE CASCADE), `title` (string, required), `subtitle` (string, nullable), `collection_question` (text, required), `theme` (ENUM with values `minimal_light`, `modern_dark`, `warm_friendly`, default `minimal_light`), `rating_enabled` (boolean, default `false`), `fields_config` (JSON, required), `public_slug` (string, unique), `public_token` (string(64), unique), `created_at` (timestamp), `updated_at` (timestamp).

The `public_slug` and `public_token` columns SHALL each have a UNIQUE constraint. The `user_id` column SHALL have an index. The `public_token` column is the security-boundary lookup used by the public submission endpoint; the `public_slug` is the human-readable URL component.

#### Scenario: Space creation populates both public URL columns

- **WHEN** a new row is inserted into `spaces` with a `user_id`, `title`, `collection_question`, and `fields_config`
- **THEN** the application layer generates a unique `public_slug` and a unique `public_token` before insert
- **AND** the row is rejected if either column would collide with an existing row's value

#### Scenario: Deleting a user removes their spaces

- **WHEN** a row in `users` is deleted
- **THEN** all rows in `spaces` with matching `user_id` are removed via `ON DELETE CASCADE`

### Requirement: Testimonials table

The system SHALL provide a `testimonials` table that stores customer submissions linked to a space.

The table SHALL contain the columns: `id` (bigIncrements primary key), `space_id` (FK to `spaces.id`, ON DELETE CASCADE), `submitter_name` (string), `submitter_email` (string), `submitter_company` (string, nullable), `submitter_social_links` (JSON, nullable), `testimonial_text` (text), `rating` (unsignedTinyInteger, nullable), `profile_photo_url` (string(2048), nullable), `field_values` (JSON, required), `consent_state` (ENUM with values `granted`, `declined`, `not_asked`), `is_favorite` (boolean, default `false`), `is_wall_of_love` (boolean, default `false`), `is_hidden` (boolean, default `false`), `submitted_at` (timestamp), `created_at` (timestamp), `updated_at` (timestamp).

The table SHALL have a `STORED` generated column `submitter_email_normalized` defined as `LOWER(TRIM(submitter_email))` and a UNIQUE constraint on `(space_id, submitter_email_normalized)`.

The table SHALL have a covering composite index `(space_id, is_wall_of_love, is_hidden, created_at)` named `testimonials_public_feed_idx` for the public embed feed query.

The table SHALL have a FULLTEXT index on `testimonial_text` for inbox search.

The table SHALL have additional indexes: `(space_id, created_at)`, `is_favorite`, `is_wall_of_love`, `submitter_name`, `submitter_email`.

#### Scenario: Duplicate submission per space is rejected

- **WHEN** an attempt is made to insert a row with `(space_id, submitter_email_normalized)` matching an existing row
- **THEN** the insert fails with a unique-constraint violation
- **AND** the application surfaces a friendly error to the submitter

#### Scenario: Normalized email collapses case and whitespace

- **WHEN** a row is inserted with `submitter_email = '  Jane@Example.COM  '`
- **THEN** the generated `submitter_email_normalized` value is `jane@example.com`
- **AND** a subsequent insert with `submitter_email = 'JANE@example.com'` violates the unique constraint on the same space

#### Scenario: Public embed feed uses the covering index

- **WHEN** a query is executed: `SELECT * FROM testimonials WHERE space_id = ? AND is_wall_of_love = 1 AND is_hidden = 0 AND consent_state = 'granted' ORDER BY created_at DESC`
- **THEN** the query plan uses the `testimonials_public_feed_idx` covering index (verified via `EXPLAIN`)
- **AND** no filesort operation is required

#### Scenario: Testimonial text is full-text searchable

- **WHEN** a query is executed: `SELECT * FROM testimonials WHERE MATCH(testimonial_text) AGAINST (? IN NATURAL LANGUAGE MODE)`
- **THEN** results are returned in relevance order from the FULLTEXT index
- **AND** short-field search on `submitter_name` and `submitter_email` uses the B-tree indexes (prefix `LIKE`)

#### Scenario: Deleting a space removes its testimonials

- **WHEN** a row in `spaces` is deleted
- **THEN** all rows in `testimonials` with matching `space_id` are removed via `ON DELETE CASCADE`

### Requirement: Embed configs table

The system SHALL provide an `embed_configs` table that stores visual configurations for public embed widgets.

The table SHALL contain the columns: `id` (bigIncrements primary key), `public_id` (UUID v4 string, unique), `space_id` (FK to `spaces.id`, ON DELETE CASCADE), `layout` (ENUM with values `masonry`, `carousel`), `theme_mode` (ENUM with values `light`, `dark`), `background_color` (string(7), hex format `#RRGGBB`), `animation_enabled` (boolean, default `true`), `created_at` (timestamp), `updated_at` (timestamp).

The `public_id` column SHALL be a UUID v4 string and SHALL be the only public-facing identifier for the embed config; it appears in the embed snippet URL `/embed/{public_id}` and SHALL be unguessable. Multiple embed configs per space are permitted.

#### Scenario: Embed config has a stable unguessable public id

- **WHEN** a new row is inserted into `embed_configs`
- **THEN** the application layer generates a UUID v4 `public_id` before insert
- **AND** the row is rejected if `public_id` would collide with an existing row's value
- **AND** the row's `id` (bigIncrements) is never exposed in the embed snippet URL

#### Scenario: Deleting a space removes its embed configs

- **WHEN** a row in `spaces` is deleted
- **THEN** all rows in `embed_configs` with matching `space_id` are removed via `ON DELETE CASCADE`
- **NOTE** this is the team's chosen behavior; the consequence is that any live embed snippets on customer sites will silently stop working when a space is deleted. Owner confirmation (§13 of the PRD) is the safeguard.

### Requirement: Subscription events table

The system SHALL provide a `subscription_events` table that stores an append-only audit log of Stripe webhook events, used for idempotency.

The table SHALL contain the columns: `id` (bigIncrements primary key), `user_id` (FK to `users.id`, nullable, ON DELETE CASCADE), `subscription_id` (FK to `subscriptions.id`, nullable, ON DELETE SET NULL), `stripe_event_id` (string, unique), `type` (string), `payload` (JSON), `processed_at` (timestamp, nullable), `created_at` (timestamp), `updated_at` (timestamp).

The `stripe_event_id` column SHALL have a UNIQUE constraint. The `payload` column SHALL contain the full Stripe event JSON for debugging and replay.

#### Scenario: Webhook idempotency via unique event id

- **WHEN** a Stripe webhook event arrives with `id = 'evt_abc123'`
- **AND** a row already exists in `subscription_events` with `stripe_event_id = 'evt_abc123'`
- **THEN** the handler detects the existing row and does not reprocess the event
- **AND** the handler returns success without side effects

#### Scenario: Late event survives subscription row deletion

- **WHEN** a Stripe webhook event arrives that references a `subscription_id` whose row has been pruned
- **THEN** the row is inserted with `subscription_id = NULL` (via `ON DELETE SET NULL`)
- **AND** the audit trail of the event is preserved

#### Scenario: Deleting a user removes their subscription events

- **WHEN** a row in `users` is deleted
- **THEN** all rows in `subscription_events` with matching `user_id` are removed via `ON DELETE CASCADE`

### Requirement: Subscriptions plan column

The system SHALL add a `plan` column to the Cashier-managed `subscriptions` table. The base `subscriptions` table is created by `php artisan cashier:install`; this requirement specifies only the additional column.

The `plan` column SHALL be an ENUM with values `free` and `pro`, default `free`, positioned after the `stripe_status` column.

The column is the denormalized cache of the user's effective plan, kept in sync with Stripe webhooks. The authoritative plan derivation (defined in `docs/data-model.md` §7.1) is a query against `subscriptions.stripe_status` and `ends_at`; the `plan` column exists for fast read-side access.

#### Scenario: New subscription row defaults to free plan

- **WHEN** a row is inserted into `subscriptions` without specifying a `plan` value
- **THEN** the row's `plan` value is `'free'`

#### Scenario: Active Stripe subscription maps to pro plan

- **WHEN** a Stripe webhook event of type `customer.subscription.updated` arrives with `status = 'active'`
- **THEN** the corresponding row in `subscriptions` is updated so that `plan = 'pro'`

#### Scenario: Canceled subscription falls back to free plan

- **WHEN** a Stripe webhook event of type `customer.subscription.deleted` arrives
- **OR** the `stripe_status` transitions to a non-`active`/non-`trialing`/non-`past_due` value
- **THEN** the corresponding row in `subscriptions` is updated so that `plan = 'free'`

### Requirement: Schema integrity invariants

The migrations, taken together, SHALL enforce the data-model invariants defined in `docs/data-model.md` §2. Specifically:

- **Dedup invariant:** At most one testimonial per `(space_id, submitter_email_normalized)`. Enforced by the `testimonials_space_email_unique` unique constraint.
- **Cascade invariant:** Deleting a `users` row removes its `spaces`, `subscriptions`, and `subscription_events` (where the FK permits). Deleting a `spaces` row removes its `testimonials` and `embed_configs`.
- **Nullability invariant:** `submitter_name`, `submitter_email`, `testimonial_text`, `field_values`, `consent_state`, `public_slug`, `public_token`, `title`, `collection_question`, `fields_config`, `theme`, `layout`, `theme_mode`, `background_color`, `rating_enabled`, `stripe_event_id`, `type`, `payload`, `plan` are all NOT NULL unless explicitly specified otherwise in their respective requirement.
- **Idempotency invariant:** A given `stripe_event_id` SHALL appear in `subscription_events` at most once. Enforced by the unique constraint.

#### Scenario: Fresh migration produces a consistent schema

- **WHEN** `php artisan migrate:fresh` is executed against an empty MySQL 8.0 database
- **THEN** all migrations run in dependency order (Laravel defaults → Cashier → `add_plan_to_subscriptions_table` → `create_spaces_table` → `create_testimonials_table` → `create_embed_configs_table` → `create_subscription_events_table`)
- **AND** no migration fails
- **AND** all foreign keys resolve
- **AND** all unique constraints and indexes are present

#### Scenario: Generated column is queryable and indexed

- **WHEN** the `testimonials` table is created
- **THEN** the `submitter_email_normalized` column is queryable as a regular column
- **AND** the `testimonials_space_email_unique` unique constraint is enforced against it
- **AND** inserting a row with mixed-case email produces the expected lowercased value in the generated column
