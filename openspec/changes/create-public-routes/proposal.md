# Proposal: Create Public Routes

## Why

The Testimonial SaaS has three classes of *public* surface — pages and endpoints that do not require authentication:

1. **The collection page** at `/c/{slug}` — a customer-facing form where a visitor submits a testimonial (PRD §6).
2. **The submission endpoint** at `POST /c/{slug}` — the form's action, persisting the testimonial and enforcing plan limits, consent, and spam protection.
3. **The embed widget** at `/embed/{public_id}` — the iframe rendered on customer websites that displays approved testimonials (PRD §8).

Without these routes, the platform has no inbound path for testimonials and no outbound path for the embed feature. The dashboard and billing pages are authenticated and a separate concern.

## What

Add the following to the project:

### Routes (`routes/web.php`)

- `GET /c/{slug:slug}` — show the public collection form. Resolves the space by `public_slug` (not `public_token` — the URL is the human-readable identifier). For security, the controller also verifies the submitted `public_token` against the form's hidden field matches the space's `public_token`, so a leaked slug can't be used to spam submissions.
- `POST /c/{slug:slug}` — process the submission via `StoreTestimonialRequest`. Returns a redirect to `/c/{slug}?submitted=1` on success (PRG pattern to prevent double-submit on refresh).
- `GET /embed/{public_id}` — render the embed widget iframe. The full-page Blade view fetches the embed config and approved testimonials, then renders them in the chosen layout (masonry or carousel) and theme (light/dark).

### Controllers

- `App\Http\Controllers\Public\SpaceController` — `show()` and `store()`.
- `App\Http\Controllers\Public\EmbedController` — `show()`.

### Views (Blade)

- `resources/views/public/space.blade.php` — the public collection form. Renders fields dynamically from `$space->fields_config`. Includes the consent checkbox, a "lighthearted confirmation" view on success, and theme-based styling.
- `resources/views/public/embed.blade.php` — the iframe content. Renders the embed layout with the testimonials list, no navigation, no auth UI.

### Services

- `App\Services\PlanResolver` — derives a user's effective plan (free vs. pro) from their `subscriptions` rows. Used by the submission endpoint to enforce the per-space testimonial limit.
- `App\Services\ConsentGuard` — the application-level guard for the consent invariant (data-model §6). The form allows `consent_state = 'declined'`, but if the owner later tries to set `is_wall_of_love = true` on a declined row, `ConsentGuard` rejects. (The MVP submission flow does not need to call this; it exists for the dashboard flow that comes later.)

## Out of Scope

- Authentication scaffolding (`auth` middleware, login/register pages) — separate change.
- The dashboard, inbox, embed builder, billing pages — separate change.
- The Stripe webhook handler.
- File upload pipeline for `profile_photo_url` — the form accepts a URL string; uploading is a separate concern.
- Rate limiting configuration (Laravel's `throttle` middleware is *applied* but the limits themselves are configured elsewhere; this change wires the middleware in).
- Custom themes beyond the three ENUM values; visual polish beyond a minimal-but-correct layout.
- Analytics on the embed widget.
- i18n / multi-language.

## Affected Capabilities

- `public-routes` — new capability spec, added by this change.

## Dependencies

- `create-database-migrations` (applied) — schema must exist.
- `create-eloquent-models` (applied) — controllers depend on `Space`, `Testimonial`, `EmbedConfig`, `User` models.
- `create-form-requests` (applied) — `StoreTestimonialRequest` is the validation contract for `POST /c/{slug}`.
- `App\Enums\Plan` PHP enum (created in this change) — referenced by `PlanResolver`.

## Risks

- **Slug + token security.** Resolving the space by `public_slug` in the URL is convenient but the slug is guessable. The submission flow therefore requires the `public_token` to be submitted alongside (as a hidden form field), and the controller verifies the two match before accepting the submission. This is a defense-in-depth measure; the slug alone is enough to *display* the form, but not to *submit* to it. Documented in the spec.
- **Double-submit protection.** The PRG (Post-Redirect-Get) pattern prevents refresh-replays. Combined with the `testimonials_space_email_normalized` unique constraint at the DB level, a determined attacker can still produce N submissions with N distinct emails; the per-space testimonial limit (data-model §7.3) caps this. Rate limiting is the next layer.
- **Iframe sandboxing.** The embed widget runs on third-party sites. The view MUST NOT include any links to the dashboard, auth pages, or any authenticated route. The view MUST NOT include any JavaScript that calls back to authenticated endpoints. Verified by the spec.
- **Theme rendering.** The `theme` ENUM is stored in the DB but the actual CSS/typography tokens live in the application layer (a config or a Blade partial). This change implements the three themes as inline-style Blade partials; a later change can move them to a proper design system.
- **Email dedup race condition.** The `(space_id, submitter_email_normalized)` unique constraint handles the race at the DB level; the controller catches the `QueryException` and returns a friendly 422 with a "duplicate submission" error. No application-level lock is needed for MVP.
- **The "lighthearted confirmation" message** (PRD §6) is implemented as a simple success view with a copy line. The "optional funny meme" is deferred — the data-model doc and PRD both flag it as optional and not blocking the core flow.
