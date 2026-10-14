# Data Model — Testimonial SaaS

**Stack:** Laravel 11 · Eloquent ORM · MySQL 8.0 · Laravel Cashier (Stripe)
**Status:** Authoritative source for entity structure, columns, relationships, indexes, and invariants.
**Scope:** MVP entities — `users`, `spaces`, `testimonials`, `embed_configs`, `subscriptions` (Cashier-native) + `subscription_events` (audit/idempotency).

This document is layered top-to-bottom: conceptual model (entities, relationships, invariants), then concrete DDL via Laravel migration schema, then validation rules, then invariants and lifecycle notes.

---

## 1. Entity Overview

| Entity | Purpose | Primary write path | Primary read path |
|---|---|---|---|
| `users` | Account, authentication, Stripe customer binding | Registration, login | Dashboard, billing |
| `spaces` | Testimonial collection project owned by one user | Owner creates in dashboard | Public submission page, owner dashboard |
| `testimonials` | A single customer submission tied to one space | Public submission form | Owner inbox, public embed feed |
| `embed_configs` | Visual configuration for a public embed widget rendered as iframe | Owner builds in dashboard | Public widget render |
| `subscriptions` | One current-state subscription row per user (Cashier) | Stripe webhook updates | Plan enforcement, billing page |
| `subscription_events` | Append-only audit/idempotency log of Stripe webhook events | Stripe webhook | Idempotency checks, debugging |

**Entity-Relationship summary:**

```
users (1) ──< (N) spaces
users (1) ──< (N) subscriptions
users (1) ──< (N) subscription_events
spaces (1) ──< (N) testimonials
spaces (1) ──< (N) embed_configs
spaces (1) ──< (N) subscription_events      [optional: which space a billing dispute was about]
```

---

## 2. Cross-Entity Invariants (enforce in code AND test)

These are the rules the data model must guarantee, beyond simple referential integrity.

1. **Consent invariant (PRD §7):** A testimonial appears in public embeds **only** if `consent_state = 'granted'` AND `is_wall_of_love = true` AND `is_hidden = false`. Owners cannot override a customer's explicit refusal (`consent_state = 'declined'`).
2. **Plan limit invariant (PRD §9):** Server-side, before a `spaces` insert or a `testimonials` insert, the application checks the owner's effective plan and rejects with a 402/403 if it would exceed:
   - Free: 3 spaces max, 100 testimonials per space
   - Pro: 25 spaces max, 1,000 testimonials per space
3. **Plan derivation (PRD §9):** Effective plan is `pro` if the user has any `subscriptions` row with `stripe_status` in `('active','trialing','past_due')` AND `ends_at IS NULL OR ends_at > NOW()`. Otherwise `free`. Denormalized `subscriptions.plan` column is the cached read-side value, but the *check* above is authoritative.
4. **Dedup invariant:** At most one testimonial per `(space_id, submitter_email_normalized)`. The DB enforces this via a unique constraint; the application surfaces a friendly error.
5. **Public-URL invariant:** A space's `/c/{slug}` URL must continue to work as long as the space exists. Deleting a space is destructive — the owner must explicitly confirm (§13).
6. **Submission timestamp invariant:** `testimonials.submitted_at` is set once at insert and never updated. `updated_at` reflects owner edits (§7).
7. **Webhook idempotency (PRD §9):** A given Stripe event id is processed at most once. Enforced by `subscription_events.stripe_event_id` unique constraint.
8. **Cascade rules (resolved):**
   - `spaces` deleted → `testimonials` and `embed_configs` cascade-delete with the space
   - `users` deleted → `subscriptions` and `subscription_events` cascade-delete; Stripe customer cancellation is the app's responsibility before user deletion
   - `embed_configs.space_id` uses `restrictOnDelete()` would be the safer default (so a live embed on a customer site never silently breaks), but the team explicitly chose `cascadeOnDelete()` for clean teardown — see §5 for the consequence.

---

## 3. Conceptual Model (Entities, Fields, Types)

Type notation below uses logical types; the MySQL types appear in §4.

### 3.1 `users`

| Field | Type | Nullable | Notes |
|---|---|---|---|
| `id` | bigIncrements | no | PK |
| `name` | string(255) | no | Display name |
| `email` | string(255) | no | Unique, login identifier |
| `email_verified_at` | timestamp | yes | Laravel default |
| `password` | string(255) | no | Hashed; Laravel default |
| `remember_token` | string(100) | yes | Laravel default |
| `stripe_id` | string(255) | yes | **Cashier column** — Stripe customer id, indexed |
| `pm_type` | string(255) | yes | **Cashier column** — last payment method type |
| `pm_last_four` | string(4) | yes | **Cashier column** — last 4 of card |
| `trial_ends_at` | timestamp | yes | **Cashier column** — trial end |
| `created_at` | timestamp | no | |
| `updated_at` | timestamp | no | |

### 3.2 `spaces`

| Field | Type | Nullable | Notes |
|---|---|---|---|
| `id` | bigIncrements | no | PK |
| `user_id` | unsignedBigInteger | no | FK → `users.id`, indexed |
| `title` | string(255) | no | Required (PRD §5) |
| `subtitle` | string(255) | yes | Optional |
| `collection_question` | text | no | Required (PRD §5) |
| `theme` | enum | no | `('minimal_light','modern_dark','warm_friendly')`, default `minimal_light` |
| `rating_enabled` | boolean | no | Default `false`; 1–5 stars assumed in app |
| `fields_config` | json | no | See §3.2.1 |
| `public_slug` | string(255) | no | Unique, used in `/c/{slug}` URL |
| `public_token` | string(64) | no | Unique, security boundary; looked up on public submission |
| `created_at` | timestamp | no | |
| `updated_at` | timestamp | no | |

#### 3.2.1 `spaces.fields_config` JSON shape

Array of objects preserving render order:

```json
[
  {"key": "name",            "type": "text",     "enabled": true,  "required": true},
  {"key": "email",           "type": "email",    "enabled": true,  "required": true},
  {"key": "testimonial_text","type": "textarea", "enabled": true,  "required": true},
  {"key": "company",         "type": "text",     "enabled": false, "required": false},
  {"key": "social_links",    "type": "url_list", "enabled": false, "required": false},
  {"key": "profile_photo",   "type": "file",     "enabled": false, "required": false}
]
```

The canonical key set lives in `app/Enums/TestimonialField.php` (or a config constant). The DB stores only the per-space overrides.

### 3.3 `testimonials`

| Field | Type | Nullable | Notes |
|---|---|---|---|
| `id` | bigIncrements | no | PK |
| `space_id` | unsignedBigInteger | no | FK → `spaces.id`, indexed |
| `submitter_name` | string(255) | no | From form |
| `submitter_email` | string(255) | no | Indexed (composite with space_id) |
| `submitter_email_normalized` | string(255) | no | Generated/stored column = `LOWER(TRIM(submitter_email))`; part of the dedup unique constraint |
| `submitter_company` | string(255) | yes | Optional, from form |
| `submitter_social_links` | json | yes | Array of URLs |
| `testimonial_text` | text | no | The long-form testimonial (FULLTEXT-indexed) |
| `rating` | unsignedTinyInteger | yes | 1–5 when `spaces.rating_enabled = true`; null otherwise |
| `profile_photo_url` | string(2048) | yes | URL of uploaded photo on CDN/S3 (PRD §12) |
| `field_values` | json | no | Raw form answers keyed by field key; see §3.3.1 |
| `consent_state` | enum | no | `('granted','declined','not_asked')` — see §6 |
| `is_favorite` | boolean | no | Default `false`, indexed |
| `is_wall_of_love` | boolean | no | Default `false`, indexed (part of public-feed index) |
| `is_hidden` | boolean | no | Default `false`, indexed (part of public-feed index) |
| `submitted_at` | timestamp | no | Set once at insert, never updated |
| `created_at` | timestamp | no | |
| `updated_at` | timestamp | no | Reflects owner edits |

#### 3.3.1 `testimonials.field_values` JSON shape

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "testimonial_text": "Absolutely loved working with...",
  "company": "Acme Co",
  "social_links": ["https://twitter.com/jane"],
  "profile_photo_url": null
}
```

The values mirror the top-level columns for fidelity. The columns are the source of truth for queries; the JSON is the form-snapshot.

### 3.4 `embed_configs`

| Field | Type | Nullable | Notes |
|---|---|---|---|
| `id` | bigIncrements | no | PK (internal) |
| `public_id` | char(36) | no | **UUID v4**, unique, used in embed snippet URL `/embed/{public_id}` |
| `space_id` | unsignedBigInteger | no | FK → `spaces.id`, indexed |
| `layout` | enum | no | `('masonry','carousel')` |
| `theme_mode` | enum | no | `('light','dark')` |
| `background_color` | string(7) | no | Hex `#RRGGBB` |
| `animation_enabled` | boolean | no | Default `true` |
| `created_at` | timestamp | no | |
| `updated_at` | timestamp | no | |

### 3.5 `subscriptions` (Cashier-native, extended)

Cashier default columns plus our additions:

| Field | Type | Nullable | Notes |
|---|---|---|---|
| `id` | bigIncrements | no | PK |
| `user_id` | unsignedBigInteger | no | FK → `users.id` |
| `name` | string(255) | no | **Cashier column** — subscription name, convention is `'default'` |
| `stripe_id` | string(255) | no | **Cashier column** — Stripe subscription id, unique |
| `stripe_status` | string(255) | no | **Cashier column** — `active`, `trialing`, `past_due`, `canceled`, etc. |
| `stripe_price` | string(255) | yes | **Cashier column** — Stripe price id |
| `quantity` | integer | yes | **Cashier column** |
| `trial_ends_at` | timestamp | yes | **Cashier column** |
| `ends_at` | timestamp | yes | **Cashier column** — set on cancel; subscription remains usable until this passes |
| `plan` | enum | no | **Our addition** — `('free','pro')` — denormalized from Stripe for fast reads |
| `created_at` | timestamp | no | |
| `updated_at` | timestamp | no | |

### 3.6 `subscription_events` (idempotency + audit)

| Field | Type | Nullable | Notes |
|---|---|---|---|
| `id` | bigIncrements | no | PK |
| `user_id` | unsignedBigInteger | yes | FK → `users.id`; null when event doesn't map to a user |
| `subscription_id` | unsignedBigInteger | yes | FK → `subscriptions.id`; null when not subscription-scoped |
| `stripe_event_id` | string(255) | no | **Unique** — used for idempotency |
| `type` | string(255) | no | Stripe event type (`customer.subscription.updated`, etc.) |
| `payload` | json | no | Full Stripe event JSON for debugging |
| `processed_at` | timestamp | yes | Null until handler runs; set on success |
| `created_at` | timestamp | no | |
| `updated_at` | timestamp | no | |

---

## 4. Concrete DDL (Laravel Migration Schema)

Generated as Laravel 11 / MySQL 8.0 migrations. Column types use `$table->...()` syntax.

### 4.1 `users` (Laravel default + Cashier columns)

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();

    // Cashier columns (added by cashier migration in production, duplicated here for the doc)
    $table->string('stripe_id')->nullable()->index();
    $table->string('pm_type')->nullable();
    $table->string('pm_last_four', 4)->nullable();
    $table->timestamp('trial_ends_at')->nullable();
});
```

### 4.2 `spaces`

```php
Schema::create('spaces', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();

    $table->string('title');
    $table->string('subtitle')->nullable();
    $table->text('collection_question');

    $table->enum('theme', ['minimal_light', 'modern_dark', 'warm_friendly'])
        ->default('minimal_light');
    $table->boolean('rating_enabled')->default(false);

    $table->json('fields_config');

    $table->string('public_slug')->unique();
    $table->string('public_token', 64)->unique();

    $table->timestamps();

    $table->index('user_id'); // already covered by foreignId but kept explicit
});
```

### 4.3 `testimonials`

```php
Schema::create('testimonials', function (Blueprint $table) {
    $table->id();
    $table->foreignId('space_id')->constrained()->cascadeOnDelete();

    $table->string('submitter_name');
    $table->string('submitter_email');
    $table->string('submitter_email_normalized'); // see migration note below
    $table->string('submitter_company')->nullable();
    $table->json('submitter_social_links')->nullable();

    $table->text('testimonial_text');
    $table->unsignedTinyInteger('rating')->nullable();

    $table->string('profile_photo_url', 2048)->nullable();

    $table->json('field_values');

    $table->enum('consent_state', ['granted', 'declined', 'not_asked']);
    $table->boolean('is_favorite')->default(false);
    $table->boolean('is_wall_of_love')->default(false);
    $table->boolean('is_hidden')->default(false);

    $table->timestamp('submitted_at');
    $table->timestamps();

    // Dedup: one testimonial per (space, normalized email)
    $table->unique(['space_id', 'submitter_email_normalized'], 'testimonials_space_email_unique');

    // Inbox index: newest first per space
    $table->index(['space_id', 'created_at']);

    // Public embed feed covering index
    $table->index(['space_id', 'is_wall_of_love', 'is_hidden', 'created_at'], 'testimonials_public_feed_idx');

    // Inbox filter indexes
    $table->index('is_favorite');
    $table->index('is_wall_of_love');

    // Inbox search
    $table->fullText('testimonial_text');
    $table->index('submitter_name');
    $table->index('submitter_email');
});
```

**Migration note for `submitter_email_normalized`:** MySQL 8.0 supports generated columns. The recommended pattern:

```php
// In a follow-up migration, or as raw SQL in the create migration:
DB::statement('ALTER TABLE testimonials
    ADD COLUMN submitter_email_normalized VARCHAR(255)
    GENERATED ALWAYS AS (LOWER(TRIM(submitter_email))) STORED');
```

Then add the unique constraint above. `STORED` (not `VIRTUAL`) is required for indexing.

### 4.4 `embed_configs`

```php
Schema::create('embed_configs', function (Blueprint $table) {
    $table->id();
    $table->uuid('public_id')->unique(); // UUID v4 generated in app
    $table->foreignId('space_id')->constrained()->cascadeOnDelete();

    $table->enum('layout', ['masonry', 'carousel']);
    $table->enum('theme_mode', ['light', 'dark']);
    $table->string('background_color', 7); // #RRGGBB
    $table->boolean('animation_enabled')->default(true);

    $table->timestamps();
});
```

### 4.5 `subscriptions` (Cashier-native, extended)

```php
// Cashier's published migration creates the base; we add only the `plan` column.
// In a separate migration, after `php artisan cashier:install`:

Schema::table('subscriptions', function (Blueprint $table) {
    $table->enum('plan', ['free', 'pro'])->default('free')->after('stripe_status');
});
```

The base Cashier table (per Laravel Cashier 14 source):
```
id, user_id, name, stripe_id (unique), stripe_status, stripe_price, quantity,
trial_ends_at, ends_at, created_at, updated_at
+ index (user_id, stripe_status)
```

### 4.6 `subscription_items` (Cashier-native, untouched)

Created by Cashier; no schema changes. Columns: `id, subscription_id, stripe_id (unique), stripe_product, stripe_price, quantity, timestamps`.

### 4.7 `subscription_events`

```php
Schema::create('subscription_events', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
    $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
    $table->string('stripe_event_id')->unique();
    $table->string('type');
    $table->json('payload');
    $table->timestamp('processed_at')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'created_at']);
});
```

`subscription_id` uses `nullOnDelete()` because Cashier may have already pruned the row by the time a late event arrives, and we want to keep the audit log intact.

---

## 5. Indexes, Constraints, and Cascade Rules (Reference)

| Constraint | Type | Table.Column | Purpose |
|---|---|---|---|
| `users.email` | UNIQUE | users.email | Login identifier |
| `users.stripe_id` | INDEX | users.stripe_id | Cashier lookup |
| `spaces.public_slug` | UNIQUE | spaces.public_slug | URL uniqueness |
| `spaces.public_token` | UNIQUE | spaces.public_token | Submission lookup |
| `spaces.user_id` | FK + INDEX | spaces.user_id | Owner listing |
| `testimonials.(space_id, submitter_email_normalized)` | UNIQUE | testimonials | Dedup invariant |
| `testimonials.(space_id, created_at)` | INDEX | testimonials | Inbox newest-first |
| `testimonials.(space_id, is_wall_of_love, is_hidden, created_at)` | INDEX | testimonials | Public embed feed (covering) |
| `testimonials.testimonial_text` | FULLTEXT | testimonials | Search |
| `testimonials.(is_favorite, is_wall_of_love)` | INDEX | testimonials | Inbox filters |
| `embed_configs.public_id` | UNIQUE | embed_configs.public_id | Snippet URL |
| `embed_configs.space_id` | FK | embed_configs.space_id | Owner listing |
| `subscriptions.stripe_id` | UNIQUE | subscriptions.stripe_id | Cashier lookup |
| `subscriptions.(user_id, stripe_status)` | INDEX | subscriptions | Cashier default |
| `subscription_events.stripe_event_id` | UNIQUE | subscription_events.stripe_event_id | Webhook idempotency |

**Cascade rules:**

| FK | On delete | Rationale |
|---|---|---|
| `spaces.user_id` → `users.id` | `cascadeOnDelete()` | User leaves → spaces go with them |
| `testimonials.space_id` → `spaces.id` | `cascadeOnDelete()` | Space deleted → testimonials go (consistent with embed_configs) |
| `embed_configs.space_id` → `spaces.id` | `cascadeOnDelete()` | Team chose this over `restrictOnDelete()` for clean teardown. **Consequence:** deleting a space with active embeds will silently break those embeds on customer sites. The owner confirmation dialog (§13) is the only safeguard. |
| `subscriptions.user_id` → `users.id` | `cascadeOnDelete()` | Local rows go; Stripe customer cancel is app-handled before user deletion |
| `subscription_events.user_id` → `users.id` | `cascadeOnDelete()` | Audit rows go with the user |
| `subscription_events.subscription_id` → `subscriptions.id` | `nullOnDelete()` | Late events survive even if subscription row pruned |

---

## 6. The Consent Invariant in Detail (PRD §7)

`consent_state` is a three-state ENUM, not a boolean. The states:

| State | Meaning | Eligible for public embed? |
|---|---|---|
| `granted` | Submitter checked the consent box | Yes (if also WoL + not hidden) |
| `declined` | Submitter explicitly declined | **No — owner cannot override** |
| `not_asked` | Form submitted without a consent step (legacy/imported/migration) | No — must be re-confirmed before publishing |

**Application rule:** the public embed feed query is effectively:

```sql
SELECT * FROM testimonials
WHERE space_id = ?
  AND is_wall_of_love = 1
  AND is_hidden = 0
  AND consent_state = 'granted'
ORDER BY created_at DESC;
```

**Test case (must exist):** an attempt by an owner to set `is_wall_of_love = true` on a row with `consent_state = 'declined'` must be rejected by the application layer (a form validation or policy check). The DB allows the write — the rule is enforced in code.

---

## 7. Plan Enforcement (PRD §9)

### 7.1 Effective plan derivation

```php
// app/Services/PlanResolver.php (not part of the data model, but the data model makes it possible)
$isPro = $user->subscriptions()
    ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
    ->where(function ($q) {
        $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
    })
    ->exists();
```

The denormalized `subscriptions.plan` column is updated by webhook handlers to keep the read path fast, but the *check* above is the authoritative derivation.

### 7.2 Limit constants (data model *describes* them, code holds them)

| Plan | Max spaces (per user) | Max testimonials (per space) |
|---|---|---|
| `free` | 3 | 100 |
| `pro` | 25 | 1,000 |

These live in `app/Enums/Plan.php` as constants. The data model is the *spec*; the enum is the *implementation*.

### 7.3 Enforced at write time

- `POST /spaces` → counts existing `spaces WHERE user_id = ?`; reject if at limit
- `POST /c/{token}` (public submission) → counts `testimonials WHERE space_id = ?`; reject if at limit

Both checks happen server-side. Both are wrapped in a transaction that re-checks the count under a row lock to prevent races.

---

## 8. Validation Rules (data model layer)

These are *column-level* validation rules. Higher-level business validation (e.g., "rating only valid when rating_enabled") lives in `FormRequest` classes, not here.

### 8.1 `spaces`

- `title`: required, string, max 255
- `subtitle`: nullable, string, max 255
- `collection_question`: required, string, max 5000
- `theme`: required, in `['minimal_light','modern_dark','warm_friendly']`
- `rating_enabled`: required, boolean
- `fields_config`: required, array, validated structurally (see §3.2.1)
- `public_slug`: required, string, max 255, regex `/^[a-z0-9-]+$/`, unique
- `public_token`: required, string, length 64, unique (generated server-side)

### 8.2 `testimonials`

- `submitter_name`: required, string, max 255
- `submitter_email`: required, email, max 255
- `testimonial_text`: required, string, max 10000
- `rating`: nullable, integer, 1–5 (only when `spaces.rating_enabled`)
- `profile_photo_url`: nullable, URL, max 2048 chars, image MIME (validated at upload time)
- `consent_state`: required, in `['granted','declined','not_asked']`
- `field_values`: required, object
- `is_favorite`, `is_wall_of_love`, `is_hidden`: boolean

### 8.3 `embed_configs`

- `layout`: required, in `['masonry','carousel']`
- `theme_mode`: required, in `['light','dark']`
- `background_color`: required, regex `/^#[0-9A-Fa-f]{6}$/`
- `animation_enabled`: boolean

### 8.4 `subscription_events`

- `stripe_event_id`: required, string, unique
- `type`: required, string
- `payload`: required, object
- `processed_at`: nullable, timestamp

---

## 9. Lifecycle & Soft State

| Entity | Hard delete? | Soft delete? | Notes |
|---|---|---|---|
| `users` | Yes (account deletion) | No | Stripe customer cancellation is app-handled before user deletion |
| `spaces` | Yes (owner confirmation) | No | Cascades to testimonials + embed_configs |
| `testimonials` | Yes (owner confirmation) | No | "Permanently delete" per PRD §7 |
| `embed_configs` | Yes (with space) | No | Cascade from spaces |
| `subscriptions` | Yes (with user) | No | Stripe state survives in Stripe itself |
| `subscription_events` | Yes (with user) | No | Audit log |

All entities have `created_at` + `updated_at`. `testimonials` additionally has immutable `submitted_at` (§3.3).

---

## 10. Open Questions / Deferred Decisions

These were intentionally deferred in the grill rounds and should be revisited in a future doc revision:

1. **Plan limits storage** — currently hardcoded in `app/Enums/Plan.php`. If a third plan lands (e.g., `team`), move limits to a `plans` table.
2. **Search backend** — `FULLTEXT` + `LIKE` is sufficient for MVP. If testimonial volume per user grows past ~100k, consider Meilisearch or Algolia.
3. **Photo storage** — `profile_photo_url` is a plain URL. Implementation will need an S3 / CDN layer; the data model is agnostic.
4. **`embed_configs` cascade consequence** — `cascadeOnDelete()` from spaces was the team's choice. If a real customer reports a broken embed due to accidental space deletion, revisit with `restrictOnDelete()` plus a "force-delete-and-warn" UX.
5. **Custom slugs** — `public_slug` is auto-generated. Pro-user custom slugs are a v2 feature.
6. **`last_login_at`** — Not in the data model. If the team needs login analytics, add a single nullable column in a follow-up migration.

---

## 11. Eloquent Relationship Map (implementation pointer)

These method names are the contract the rest of the app uses:

```php
// User
$user->spaces();           // hasMany Space
$user->subscriptions();    // hasMany Subscription (Cashier)
$user->subscriptionEvents(); // hasMany SubscriptionEvent

// Space
$space->owner();           // belongsTo User (FK: user_id, local key: id)
$space->user();            // alias of owner() — keep one, prefer owner()
$space->testimonials();    // hasMany Testimonial
$space->embedConfigs();    // hasMany EmbedConfig

// Testimonial
$testimonial->space();     // belongsTo Space

// EmbedConfig
$embedConfig->space();     // belongsTo Space

// Subscription
$subscription->user();     // belongsTo User (Cashier)
$subscription->subscriptionItems(); // hasMany SubscriptionItem (Cashier)
$subscription->events();   // hasMany SubscriptionEvent (we add this)

// SubscriptionEvent
$event->user();            // belongsTo User (nullable)
$event->subscription();    // belongsTo Subscription (nullable)
```

Note: the column is `user_id`, the method is `owner()` on `Space` for clarity. The column name stays consistent with `subscriptions.user_id` and `testimonials.space_id` — i.e., the FK is named after the *parent* table.
