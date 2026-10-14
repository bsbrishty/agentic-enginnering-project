# Capability: eloquent-models

The Eloquent ORM bindings for the Testimonial SaaS schema. This delta spec defines the contract for the model classes that wrap the tables created by `create-database-migrations`. After this change archives, the spec merges into `openspec/specs/eloquent-models/spec.md` as the canonical contract.

The relationship method names and column names match `docs/data-model.md` §11 verbatim.

## ADDED Requirements

### Requirement: User model exposes subscriptions, spaces, and subscription events

The `User` model SHALL extend `Illuminate\Foundation\Auth\User` (Laravel's `Authenticatable`) and SHALL use the `Laravel\Cashier\Billable` trait.

The model SHALL declare the following relationship methods:

- `subscriptions(): HasMany` — provided by the `Billable` trait, returns Cashier's `Subscription` model.
- `spaces(): HasMany` — local definition, returns `Space::class`.
- `subscriptionEvents(): HasMany` — local definition, returns `SubscriptionEvent::class`.

The model SHALL declare `$fillable` containing the standard auth columns (`name`, `email`, `password`) plus the four Cashier columns (`stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at`).

The model SHALL cast `email_verified_at` and `trial_ends_at` to `datetime`.

The model SHALL keep `password` and `remember_token` in `$hidden` (Laravel's default for the `Authenticatable` base class).

#### Scenario: User can be subscribed via Cashier

- **WHEN** a `User` is created and a `Subscription` row exists for them via Cashier
- **THEN** `$user->subscribed('default')` returns `true` for an active subscription
- **AND** `$user->subscription('default')` returns the Cashier `Subscription` model

#### Scenario: User can list owned spaces

- **WHEN** a `User` has created three `Space` rows
- **THEN** `$user->spaces` is a `Collection` of three `Space` instances
- **AND** `$user->spaces()->count()` returns 3

#### Scenario: User can list subscription events

- **WHEN** a `User` has five `SubscriptionEvent` rows referencing them
- **THEN** `$user->subscriptionEvents` is a `Collection` of five `SubscriptionEvent` instances

### Requirement: Space model auto-generates public URL identifiers on create

The `Space` model SHALL extend `Illuminate\Database\Eloquent\Model`.

The model SHALL declare `$fillable` with: `user_id`, `title`, `subtitle`, `collection_question`, `theme`, `rating_enabled`, `fields_config`, `public_slug`, `public_token`.

The model SHALL cast `rating_enabled` to `boolean` and `fields_config` to `array`.

The model SHALL declare the following relationship methods:

- `owner(): BelongsTo` — `belongsTo(User::class, 'user_id')`. The method is named `owner()` for clarity; the underlying foreign-key column is `user_id`.
- `testimonials(): HasMany` — `hasMany(Testimonial::class)`.
- `embedConfigs(): HasMany` — `hasMany(EmbedConfig::class)`.

The model SHALL register a `creating` model event in a `booted()` method that:

- Generates a `public_slug` of the form `Str::slug($space->title) . '-' . Str::random(6)` if the field is empty.
- Generates a `public_token` of `Str::random(64)` if the field is empty.
- Does not overwrite an explicitly provided value.

The model SHALL be tolerant of an empty `title` during `public_slug` generation by falling back to a slug derived from a random token (so the slug is never blank or invalid).

#### Scenario: Creating a space fills public identifiers automatically

- **WHEN** a `Space` is created via `$space = Space::create([...])` without specifying `public_slug` or `public_token`
- **THEN** `$space->public_slug` is a non-empty slug-shaped string ending in a 6-character random suffix
- **AND** `$space->public_token` is a 64-character random string
- **AND** the row is persisted with both values populated

#### Scenario: Explicit identifiers are preserved

- **WHEN** a `Space` is created with an explicit `public_slug` and `public_token`
- **THEN** those values are stored verbatim
- **AND** the `creating` hook does not overwrite them

#### Scenario: Owner relationship resolves

- **WHEN** a `Space` is created with `user_id = $user->id`
- **THEN** `$space->owner` returns the `$user` instance
- **AND** `$space->owner->id === $user->id`

#### Scenario: Testimonials and embed configs are queryable

- **WHEN** a `Space` has two `Testimonial` rows and three `EmbedConfig` rows
- **THEN** `$space->testimonials->count() === 2`
- **AND** `$space->embedConfigs->count() === 3`

### Requirement: Testimonial model casts JSON columns and date fields

The `Testimonial` model SHALL extend `Illuminate\Database\Eloquent\Model`.

The model SHALL declare `$fillable` with: `space_id`, `submitter_name`, `submitter_email`, `submitter_company`, `submitter_social_links`, `testimonial_text`, `rating`, `profile_photo_url`, `field_values`, `consent_state`, `is_favorite`, `is_wall_of_love`, `is_hidden`, `submitted_at`.

The model SHALL cast:

- `is_favorite`, `is_wall_of_love`, `is_hidden` to `boolean`.
- `submitter_social_links`, `field_values` to `array`.
- `submitted_at` to `datetime`.
- `rating` to `integer`.
- `consent_state` to `string`.

The model SHALL declare `space(): BelongsTo` returning `belongsTo(Space::class)`.

The model SHALL NOT have any `booted()` hook; all identifier and constraint logic lives in the database layer (generated column, unique constraint).

#### Scenario: JSON column cast round-trips

- **WHEN** a `Testimonial` is created with `field_values = ['name' => 'Jane', 'email' => 'jane@example.com']`
- **THEN** `$testimonial->fresh()->field_values` is a PHP array containing the same key-value pairs

#### Scenario: Booleans are read as booleans

- **WHEN** a `Testimonial` is created with `is_wall_of_love = 1` (raw insert) or `true` (PHP)
- **THEN** `$testimonial->is_wall_of_love` is the PHP boolean `true`
- **AND** `$testimonial->is_hidden` is the PHP boolean `false` after a row with no value is fetched (default)

#### Scenario: Submitted-at is a Carbon instance

- **WHEN** a `Testimonial` is created with `submitted_at = now()`
- **THEN** `$testimonial->submitted_at` is a `Carbon\Carbon` instance

#### Scenario: Space relationship resolves

- **WHEN** a `Testimonial` is associated with a `Space`
- **THEN** `$testimonial->space` returns that `Space` instance

### Requirement: EmbedConfig model auto-generates public id on create

The `EmbedConfig` model SHALL extend `Illuminate\Database\Eloquent\Model`.

The model SHALL declare `$fillable` with: `public_id`, `space_id`, `layout`, `theme_mode`, `background_color`, `animation_enabled`.

The model SHALL cast `animation_enabled` to `boolean` and `layout` / `theme_mode` to `string`.

The model SHALL declare `space(): BelongsTo` returning `belongsTo(Space::class)`.

The model SHALL register a `creating` model event in a `booted()` method that generates a UUID v4 `public_id` via `(string) Str::uuid()` if the field is empty.

#### Scenario: Public id is auto-filled

- **WHEN** an `EmbedConfig` is created without specifying `public_id`
- **THEN** `$embedConfig->public_id` matches the regex `/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/` (UUID v4)

#### Scenario: Two configs get distinct public ids

- **WHEN** two `EmbedConfig` rows are created in succession without explicit `public_id`
- **THEN** their `public_id` values are different
- **AND** each row is persisted successfully (the unique constraint is not violated)

#### Scenario: Explicit public id is preserved

- **WHEN** an `EmbedConfig` is created with an explicit `public_id`
- **THEN** that value is stored verbatim
- **AND** the `creating` hook does not overwrite it

#### Scenario: Space relationship resolves

- **WHEN** an `EmbedConfig` is associated with a `Space`
- **THEN** `$embedConfig->space` returns that `Space` instance

### Requirement: SubscriptionEvent model maps nullable foreign keys

The `SubscriptionEvent` model SHALL extend `Illuminate\Database\Eloquent\Model`.

The model SHALL declare `$fillable` with: `user_id`, `subscription_id`, `stripe_event_id`, `type`, `payload`, `processed_at`.

The model SHALL cast `payload` to `array` and `processed_at` to `datetime`.

The model SHALL declare:

- `user(): BelongsTo` returning `belongsTo(User::class)`.
- `subscription(): BelongsTo` returning `belongsTo(\Laravel\Cashier\Subscription::class)`.

The model SHALL NOT enforce idempotency at the application layer; the unique constraint on `stripe_event_id` is the source of truth (data-model §3.6 and migration §4.7).

#### Scenario: Idempotency surfaces as QueryException

- **WHEN** a `SubscriptionEvent` is created with `stripe_event_id = 'evt_abc'`
- **AND** a second `SubscriptionEvent` is created with the same `stripe_event_id`
- **THEN** the second insert throws `Illuminate\Database\QueryException` with a unique-constraint error

#### Scenario: Payload round-trips

- **WHEN** a `SubscriptionEvent` is created with `payload = ['id' => 'evt_abc', 'type' => 'customer.subscription.updated']`
- **THEN** `$event->fresh()->payload` is a PHP array containing the same keys and values

#### Scenario: Nullable user and subscription

- **WHEN** a `SubscriptionEvent` is created with `user_id = null` and `subscription_id = null`
- **THEN** the row is persisted
- **AND** `$event->user` and `$event->subscription` are both `null`

#### Scenario: Relationships resolve when set

- **WHEN** a `SubscriptionEvent` is created with valid `user_id` and `subscription_id`
- **THEN** `$event->user` returns a `User` instance
- **AND** `$event->subscription` returns a `Laravel\Cashier\Subscription` instance

### Requirement: Model integrity invariants

The Eloquent models, taken together, SHALL respect the data-model invariants defined in `docs/data-model.md` §2. Specifically:

- **Cascade invariant:** Deleting a `User` row removes its `Space`, `Subscription`, and `SubscriptionEvent` rows automatically via the FK cascades. Models SHALL NOT define application-level deletion listeners that duplicate this work.
- **No soft delete:** No model uses the `SoftDeletes` trait. Deletes are final.
- **Generated column is read-only:** Eloquent SHALL NOT attempt to write to `testimonials.submitter_email_normalized`. It is a generated column; the application must only set `submitter_email`, and the DB computes the rest.
- **Public identifiers are auto-generated unless explicit:** `Space.public_slug`, `Space.public_token`, and `EmbedConfig.public_id` are auto-generated by the model's `booted()` hook. Explicit values are preserved.
- **Unique-constraint failures are surfaced, not swallowed:** All unique constraints (public_slug, public_token, public_id, stripe_event_id, the testimonial dedup) propagate as `QueryException`; no model wraps them in a try/catch.

#### Scenario: Cascade delete through Eloquent

- **WHEN** a `User` with one `Space` (with one `Testimonial` and one `EmbedConfig`) is deleted via `$user->delete()`
- **THEN** the corresponding `Space`, `Testimonial`, and `EmbedConfig` rows are removed
- **AND** no manual cleanup code ran

#### Scenario: Generated column is not writable

- **WHEN** a `Testimonial::create([...])` is called without specifying `submitter_email_normalized`
- **THEN** the row is inserted successfully
- **AND** the column is computed by MySQL on the next read
- **WHEN** a `Testimonial::create([..., 'submitter_email_normalized' => 'hacker'])` is attempted
- **THEN** MySQL rejects the write (generated columns are not user-writable) and Eloquent throws `QueryException`

#### Scenario: Booted hook only fires on create, not update

- **WHEN** a `Space` is created and then `$space->update(['title' => 'New Title'])` is called
- **THEN** `public_slug` and `public_token` retain their original values
- **AND** the `creating` hook does not re-run on update
