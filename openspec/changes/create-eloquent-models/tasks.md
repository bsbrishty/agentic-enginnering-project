# Tasks: Create Eloquent Models

> **Status:** All model files have been written into `app/Models/`.
> The "verify" tasks at the end of each section require a real Laravel
> environment (`php artisan tinker`, `php artisan migrate:fresh`, etc.) to
> execute and are left unchecked for the implementing engineer to run.

## 1. Prerequisites

- [ ] 1.1 Confirm Laravel 11 project skeleton exists at the repository root.
- [ ] 1.2 Confirm `composer.json` includes `laravel/cashier`.
- [ ] 1.3 Confirm `database/migrations/` contains the five migrations from `create-database-migrations` and that `php artisan migrate:fresh` runs cleanly.

## 2. `User` Model

- [x] 2.1 Add `use Laravel\Cashier\Billable;` to `app/Models/User.php`.
- [x] 2.2 Add the `Billable` trait inside the class body.
- [x] 2.3 Add `spaces()` and `subscriptionEvents()` relationship methods.
- [x] 2.4 Confirm `subscriptions()` (provided by `Billable`) is present and returns a `HasMany` on the Cashier `Subscription` model.
- [x] 2.5 Add explicit `$fillable` matching Laravel's default user columns plus the four Cashier columns (`stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at`).
- [x] 2.6 Cast `email_verified_at` and `trial_ends_at` to `datetime`.
- [x] 2.7 Confirm the model's `$hidden` keeps `password`, `remember_token` hidden from JSON serialization.
- [ ] 2.8 Verify in `php artisan tinker` that `User::factory()->create()` succeeds and that `$user->subscriptions()` returns a `HasMany`.

> File written: `app/Models/User.php`

## 3. `Space` Model

- [x] 3.1 Create `app/Models/Space.php` extending `Illuminate\Database\Eloquent\Model`.
- [x] 3.2 Set `$table = 'spaces'` (Laravel default but kept explicit for clarity).
- [x] 3.3 Define `$fillable` with: `user_id`, `title`, `subtitle`, `collection_question`, `theme`, `rating_enabled`, `fields_config`, `public_slug`, `public_token`.
- [x] 3.4 Cast `rating_enabled` to `boolean`, `fields_config` to `array`, and `theme` to a string-backed enum or `string` (per data-model §4.2 — three-value ENUM).
- [x] 3.5 Implement `owner()` returning `belongsTo(User::class, 'user_id')`.
- [x] 3.6 Implement `testimonials()` returning `hasMany(Testimonial::class)`.
- [x] 3.7 Implement `embedConfigs()` returning `hasMany(EmbedConfig::class)`.
- [x] 3.8 Add a `booted()` method with a `creating` hook that:
  - Generates a unique `public_slug` of the form `Str::slug($space->title) . '-' . Str::random(6)`.
  - Generates a `public_token` of `Str::random(64)`.
  - Only fills the field if it's empty (so explicit values pass through).
- [ ] 3.9 Verify in `php artisan tinker`:
  - `Space::factory()->make()` (or a manual create) produces a `public_slug` and `public_token` automatically.
  - `$space->owner` resolves to a `User`.
  - `$space->testimonials` is a `Collection` (possibly empty).
  - `$space->embedConfigs` is a `Collection` (possibly empty).
  - Two spaces created with the same title produce different `public_slug` values (because of the random suffix).

> File written: `app/Models/Space.php`

## 4. `Testimonial` Model

- [x] 4.1 Create `app/Models/Testimonial.php` extending `Model`.
- [x] 4.2 Define `$fillable` with: `space_id`, `submitter_name`, `submitter_email`, `submitter_company`, `submitter_social_links`, `testimonial_text`, `rating`, `profile_photo_url`, `field_values`, `consent_state`, `is_favorite`, `is_wall_of_love`, `is_hidden`, `submitted_at`.
- [x] 4.3 Cast booleans (`is_favorite`, `is_wall_of_love`, `is_hidden`) to `boolean`.
- [x] 4.4 Cast JSON columns (`submitter_social_links`, `field_values`) to `array`.
- [x] 4.5 Cast `submitted_at` to `datetime`.
- [x] 4.6 Cast `consent_state` and `rating` to their native types (string and integer respectively).
- [x] 4.7 Implement `space()` returning `belongsTo(Space::class)`.
- [ ] 4.8 Verify in `php artisan tinker`:
  - Creating a `Testimonial` with a mixed-case `submitter_email` and re-reading it back returns the `submitter_email_normalized` generated column's value when explicitly queried via raw SQL (Eloquent does not see generated columns by default).
  - `$testimonial->space` resolves to a `Space`.
  - `$testimonial->field_values` is a PHP array.

> File written: `app/Models/Testimonial.php`

## 5. `EmbedConfig` Model

- [x] 5.1 Create `app/Models/EmbedConfig.php` extending `Model`.
- [x] 5.2 Define `$fillable` with: `public_id`, `space_id`, `layout`, `theme_mode`, `background_color`, `animation_enabled`.
- [x] 5.3 Cast `animation_enabled` to `boolean`.
- [x] 5.4 Cast `layout` and `theme_mode` to strings (ENUM-backed).
- [x] 5.5 Implement `space()` returning `belongsTo(Space::class)`.
- [x] 5.6 Add a `booted()` method with a `creating` hook that generates a UUID v4 `public_id` via `(string) Str::uuid()` if empty.
- [ ] 5.7 Verify in `php artisan tinker`:
  - Creating an `EmbedConfig` auto-fills `public_id` with a valid UUID v4 string.
  - Two configs created in succession have different `public_id` values.
  - `$embedConfig->space` resolves to a `Space`.

> File written: `app/Models/EmbedConfig.php`

## 6. `SubscriptionEvent` Model

- [x] 6.1 Create `app/Models/SubscriptionEvent.php` extending `Model`.
- [x] 6.2 Define `$fillable` with: `user_id`, `subscription_id`, `stripe_event_id`, `type`, `payload`, `processed_at`.
- [x] 6.3 Cast `payload` to `array`.
- [x] 6.4 Cast `processed_at` to `datetime`.
- [x] 6.5 Implement `user()` returning `belongsTo(User::class)`.
- [x] 6.6 Implement `subscription()` returning `belongsTo(\Laravel\Cashier\Subscription::class)`.
- [ ] 6.7 Verify in `php artisan tinker`:
  - `SubscriptionEvent::create([...])` succeeds with all required fields.
  - Inserting a row with a duplicate `stripe_event_id` throws a `QueryException` (unique constraint).
  - `$event->user` and `$event->subscription` resolve correctly when set.

> File written: `app/Models/SubscriptionEvent.php`

## 7. End-to-End Verification

- [ ] 7.1 Run `php artisan migrate:fresh` from a clean state. Must complete without errors.
- [ ] 7.2 In `php artisan tinker`, create a `User`, then a `Space` for that user, then a `Testimonial` for that space, then two `EmbedConfig`s for that space. Verify the relationship graph resolves in both directions.
- [ ] 7.3 Verify cascade behavior: deleting the `User` removes the `Space`, which removes the `Testimonial` and `EmbedConfig`s, all via FK cascade (no application-level cleanup).
- [ ] 7.4 Verify the `public_slug`/`public_token`/`public_id` uniqueness is enforced at the DB level (try inserting a row with a duplicate value and expect a `QueryException`).
- [ ] 7.5 Verify the dedup invariant: two `Testimonial` rows in the same space with case-different `submitter_email` values that lower-case to the same string trigger a unique-constraint violation.

## 8. Documentation Cross-Check

- [ ] 8.1 Compare the final model files against `docs/data-model.md` §11 line-by-line. Any deviation in relationship method names is a contract violation; do not silently rename.
- [ ] 8.2 Confirm that the `User` model in the project skeleton is the version written here, not a stale one with only the original auth columns.
- [ ] 8.3 Note any deferred decisions encountered during implementation in `docs/data-model.md` §10.
