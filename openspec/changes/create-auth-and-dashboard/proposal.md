# Proposal: Create Authentication and Owner Dashboard

## Why

The Testimonial SaaS is a multi-tenant product (PRD §2): each `User` owns spaces, and the dashboard is where owners manage their spaces, the testimonials they collect, and the embed widgets that display approved testimonials. The public routes change (`create-public-routes`) gave the platform inbound and outbound paths for testimonials; this change gives the platform the *owner-facing* experience that ties them together.

The PRD requires:

- **Authentication** (PRD §3): signup, login, logout, password reset, with all dashboard routes protected.
- **Dashboard** (PRD §4): statistics, recent testimonials, current plan + usage, upgrade banner, quick links.
- **Space management** (PRD §5): full CRUD on spaces with the configurable form fields, themes, ratings, and the public URL flow.
- **Testimonial inbox & management** (PRD §7): paginated list, filters, search, and the per-row actions (favorite, Wall of Love, edit, hide/unhide, delete).
- **Embed widget builder** (PRD §8): configure layout/theme/background/animation, live preview, copyable snippet.

## What

This change adds the authenticated surface of the application. Specifically:

### Authentication scaffold

- `routes/auth.php` — registration, login, password reset, logout, email verification routes. Implementation uses Laravel's built-in auth controllers (not Breeze/Jetstream, to keep the change self-contained).
- `App\Http\Controllers\Auth\RegisteredUserController` — `create()`, `store()`.
- `App\Http\Controllers\Auth\AuthenticatedSessionController` — `create()`, `store()`, `destroy()`.
- `App\Http\Controllers\Auth\PasswordResetLinkController` — `create()`, `store()`.
- `App\Http\Controllers\Auth\NewPasswordController` — `create()`, `store()`.
- Blade views: `auth/login.blade.php`, `auth/register.blade.php`, `auth/forgot-password.blade.php`, `auth/reset-password.blade.php`.

The `User` model already has the right columns (Laravel default + Cashier additions from `create-eloquent-models`). The auth scaffold plugs into those columns directly.

### Owner-facing routes (all under `auth` middleware)

- `GET /dashboard` — Dashboard\DashboardController@index. Statistics, recent testimonials, plan/usage, quick links.
- Resource routes for `Space` at `/dashboard/spaces` — full CRUD: index, create, store, show, edit, update, destroy. Uses the form requests from `create-form-requests`.
- `GET /dashboard/inbox` — TestimonialInboxController@index. Paginated list with filters, search, and per-row actions.
- `PATCH /dashboard/testimonials/{testimonial}` — TestimonialInboxController@update. Handles favorite / WoL / hidden / edit.
- `DELETE /dashboard/testimonials/{testimonial}` — TestimonialInboxController@destroy.
- Resource routes for `EmbedConfig` at `/dashboard/spaces/{space}/embeds` — index, create, store, edit, update, destroy. Live preview on the edit/create views.
- `GET /dashboard/billing` — BillingController@index. Current plan, usage, upgrade button for free users. (Stripe Checkout is wired in a separate change; this just shows the page.)

### Controllers

- `App\Http\Controllers\Dashboard\DashboardController`
- `App\Http\Controllers\Dashboard\SpaceController` (replaces the public one in the `Public` namespace; both coexist for now since the file tree is namespaced)
- `App\Http\Controllers\Dashboard\TestimonialInboxController`
- `App\Http\Controllers\Dashboard\EmbedConfigController`
- `App\Http\Controllers\Dashboard\BillingController`

### Policies

- `App\Policies\SpacePolicy` — `view`, `update`, `delete`: owner-only.
- `App\Http\Controllers\Dashboard\TestimonialInboxController` uses ad-hoc `$user->id === $testimonial->space->user_id` checks (a `TestimonialPolicy` is added in this change for clarity).

### Views

- `layouts/dashboard.blade.php` — sidebar navigation, top bar with user menu, main content area.
- `dashboard/index.blade.php` — stats cards, recent testimonials, plan/usage, upgrade banner, quick links.
- `dashboard/spaces/index.blade.php` — list of owner's spaces with create button.
- `dashboard/spaces/create.blade.php` — creation form using the theme/fields/rating fields.
- `dashboard/spaces/edit.blade.php` — same as create, plus public URL display with copy button.
- `dashboard/spaces/show.blade.php` — single space overview (testimonial count, embed configs, public URL).
- `dashboard/inbox/index.blade.php` — paginated list with filters, search, per-row actions.
- `dashboard/embeds/index.blade.php` — list of embed configs for a space.
- `dashboard/embeds/create.blade.php` / `edit.blade.php` — embed builder form with live preview.
- `dashboard/billing/index.blade.php` — current plan, usage bars, upgrade button.

### Service additions

- `App\Services\SpaceService` — encapsulates the dashboard-side space creation/update logic, including the plan-limit check on space count and the public_url display.
- `App\Services\InboxService` — encapsulates the inbox query (filter, search, paginate) so the controller stays thin.

## Out of Scope

- **Stripe Checkout integration** (PRD §9) — the Billing page shows the upgrade button but does not yet create a Stripe Checkout session. That's `create-stripe-billing`.
- **Stripe webhook handler** — `create-stripe-webhook-handler`.
- **File upload pipeline for `profile_photo_url`** — the form accepts a URL string. Real upload (S3, signed URLs) is a separate change.
- **Email sending** (welcome email, password reset email) — Laravel's `MustVerifyEmail` and `ResetPassword` notifications work out of the box; the views are minimal.
- **Dashboard analytics** beyond the four stat cards (no charts library wiring in this change; the dashboard lists counts and a "recent" list. A chart library comes in a follow-up).
- **Team collaboration / multiple paid tiers** (deferred per PRD §15 scope constraint).
- **i18n** beyond English.

## Affected Capabilities

- `auth-and-dashboard` — new capability spec, added by this change.

## Dependencies

- `create-database-migrations` (applied)
- `create-eloquent-models` (applied)
- `create-form-requests` (applied) — `StoreSpaceRequest`, `UpdateSpaceRequest`, `StoreEmbedConfigRequest` are reused
- `create-public-routes` (applied) — the `Public\SpaceController` continues to live alongside the dashboard `SpaceController`; no conflict because they're in different namespaces
- `App\Services\PlanResolver` and `App\Services\ConsentGuard` (created in `create-public-routes`)

## Risks

- **Two `SpaceController` classes.** The public one is at `App\Http\Controllers\Public\SpaceController`; this change adds `App\Http\Controllers\Dashboard\SpaceController`. They have different route prefixes, different middleware, and different concerns. A future consolidation could merge them, but for the MVP clarity is better.
- **Route model binding for `Space` resolves by `public_slug` (from `create-public-routes`).** Dashboard routes like `/dashboard/spaces/{space}/edit` will receive a `Space` resolved by slug, not by id. This is intentional — the slug is stable, unguessable-enough for the dashboard's own use, and the same lookup mechanism works across public and dashboard. If a future change needs to look up by id, it should use `Space::find($id)` explicitly.
- **Wall of Love toggle must use `ConsentGuard`.** The `TestimonialInboxController::update` action MUST call `ConsentGuard::assertCanPublish()` before persisting `is_wall_of_love = true`. The dashboard is the most likely place to forget this. The spec calls it out as a hard requirement with a test scenario.
- **Inbox search performance.** The `FULLTEXT` index on `testimonial_text` plus the B-tree indexes on `submitter_name` and `submitter_email` (from `create-database-migrations`) cover the common cases. A `LIKE 'term%'` on name/email uses the B-tree; a `MATCH ... AGAINST` on text uses the FULLTEXT. The inbox query is careful to use the right one.
- **Plan limit on space count.** Creating a new space must check the owner's existing space count against the plan's `maxSpaces()`. This is a different code path from the per-space testimonial limit (which lives in the public submission flow). Both are in this change.
- **No CSRF on auth routes by default.** Laravel's `web` middleware group includes `VerifyCsrfToken`; all forms posted to the dashboard are CSRF-protected. This is automatic; we don't override.
- **Password reset flow uses Laravel's default `Password` broker.** No customization in this change.
