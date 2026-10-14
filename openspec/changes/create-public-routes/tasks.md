# Tasks: Create Public Routes

> **Status:** All routes, controllers, services, and Blade views have been written.
> The "verify" tasks at the end of each section require a real Laravel environment
> (running server, `php artisan tinker`, browser) to execute and are left unchecked
> for the implementing engineer to run.

## 1. Prerequisites

- [ ] 1.1 Confirm Laravel 11 project skeleton exists at the repository root.
- [ ] 1.2 Confirm `composer.json` includes `laravel/cashier`.
- [ ] 1.3 Confirm `database/migrations/` and `app/Models/` are in place (from previous changes).
- [ ] 1.4 Confirm `app/Http/Requests/StoreTestimonialRequest.php` exists (from `create-form-requests`).
- [ ] 1.5 Confirm `php artisan migrate:fresh` runs cleanly against MySQL 8.0.

## 2. `Plan` Enum and `PlanResolver` Service

- [x] 2.1 Create `app/Enums/Plan.php` with two cases: `Free` and `Pro`. Expose the limit constants from data-model §7.2.
- [x] 2.2 Create `app/Services/PlanResolver.php` with a `resolve(User $user): Plan` method that returns `Pro` if the user has any active Cashier subscription, otherwise `Free`. Implementation per data-model §7.1.
- [ ] 2.3 Verify in `php artisan tinker`:
  - A user with no subscription row gets `PlanResolver::resolve($user) === Plan::Free`.
  - A user with an active `subscriptions` row gets `Plan::Pro`.
  - A user with a `canceled` row gets `Plan::Free`.

> Files written: `app/Enums/Plan.php`, `app/Services/PlanResolver.php`

## 3. `ConsentGuard` Service

- [x] 3.1 Create `app/Services/ConsentGuard.php` with an `assertCanPublish(Testimonial $t): void` method that throws `ConsentViolationException` if `$t->consent_state === 'declined'`.
- [x] 3.2 Create `app/Exceptions/ConsentViolationException.php` extending `RuntimeException` with a public `$testimonial` property.
- [ ] 3.3 Verify: calling `ConsentGuard::assertCanPublish($t)` on a row with `consent_state = 'granted'` returns normally; on `'declined'` throws the exception; on `'not_asked'` throws (cannot publish without explicit consent).

> Files written: `app/Services/ConsentGuard.php`, `app/Exceptions/ConsentViolationException.php`

## 4. Public Space Routes

- [x] 4.1 Add the following routes to `routes/web.php`:
  - `GET /c/{slug:slug}` → `Public\SpaceController@show`
  - `POST /c/{slug:slug}` → `Public\SpaceController@store`
- [x] 4.2 Apply the `throttle:public-submission` middleware to `POST /c/{slug:slug}` (configured elsewhere; this change only wires the middleware).
- [x] 4.3 No `auth` middleware on either route.

## 5. `SpaceController`

- [x] 5.1 Create `app/Http/Controllers/Public/SpaceController.php` extending `Controller`.
- [x] 5.2 Implement `show(Space $space)`:
  - Resolves the space via route model binding by `public_slug` (the `slug` route param).
  - Returns the `public.space` Blade view with the `$space` and a `submitted` flash flag from the query string.
- [x] 5.3 Implement `store(StoreTestimonialRequest $request, Space $space)`:
  - Verifies `$request->input('public_token') === $space->public_token`; on mismatch, abort 403.
  - Counts existing testimonials in the space; if at the per-space limit for the owner's plan, abort 422 with a friendly message.
  - Creates the testimonial via `$space->testimonials()->create($request->validated() + ['submitted_at' => now()])`.
  - Catches the unique-constraint violation (`QueryException` with code 23000) and returns 422 with a "duplicate submission" error.
  - Redirects to `/c/{slug}?submitted=1`.
- [ ] 5.4 Verify with feature tests:
  - `GET /c/{slug}` returns 200 with the form.
  - `POST /c/{slug}` with valid payload returns 302 to `/c/{slug}?submitted=1`.
  - `POST /c/{slug}` with mismatched `public_token` returns 403.
  - `POST /c/{slug}` at the per-space limit returns 422.
  - `POST /c/{slug}` with a duplicate email returns 422.

> File written: `app/Http/Controllers/Public/SpaceController.php`

## 6. Public Space View

- [x] 6.1 Create `resources/views/public/space.blade.php` extending a minimal `layouts.public` layout.
- [x] 6.2 Apply the theme by including `partials.theme-{theme}` based on `$space->theme`.
- [x] 6.3 Render the form dynamically: for each entry in `$space->fields_config` where `enabled === true`, render the corresponding input. Required fields get `required`; the `testimonial_text` field renders as `<textarea>`; `profile_photo` renders as a URL input.
- [x] 6.4 Include a consent checkbox that submits `consent_state` as either `granted` or `declined` via a hidden field set by JS or as two radio inputs.
- [x] 6.5 Include the rating input if `$space->rating_enabled`.
- [x] 6.6 Include a hidden `public_token` field with `$space->public_token`.
- [x] 6.7 If `?submitted=1` is in the query string, show the "lighthearted" confirmation view instead of the form (PRD §6: "You're officially awesome! 🎉 ...").
- [ ] 6.8 Verify in a browser: the form renders for a known slug, all enabled fields appear, required fields are marked, submission redirects to the success state, the success state shows the friendly message.

> File written: `resources/views/public/space.blade.php`

## 7. Public Embed Route

- [x] 7.1 Add `GET /embed/{public_id}` → `Public\EmbedController@show` to `routes/web.php`.
- [x] 7.2 No `auth` middleware. This route is intended to be loaded inside an iframe on a third-party site.

## 8. `EmbedController`

- [x] 8.1 Create `app/Http/Controllers/Public/EmbedController.php`.
- [x] 8.2 Implement `show(EmbedConfig $embedConfig)`:
  - Resolves the embed config by `public_id` via route model binding.
  - Loads the parent `Space` via `$embedConfig->space`.
  - Queries the eligible testimonials: `is_wall_of_love = true`, `is_hidden = false`, `consent_state = 'granted'`, ordered by `created_at DESC`.
  - Returns the `public.embed` Blade view with `$embedConfig`, `$space`, and `$testimonials`.
- [x] 8.3 Sets the response headers to disable caching of personalized content (this is public content, but `Cache-Control: public, max-age=60` is appropriate to limit how often a third-party site hits our DB).
- [ ] 8.4 Verify with feature tests:
  - `GET /embed/{public_id}` returns 200 for a known `public_id`.
  - The response includes only testimonials with `is_wall_of_love = 1`, `is_hidden = 0`, `consent_state = 'granted'`.
  - The response does NOT include any testimonials with `consent_state = 'declined'` even if `is_wall_of_love = 1`.

> File written: `app/Http/Controllers/Public/EmbedController.php`

## 9. Public Embed View

- [x] 9.1 Create `resources/views/public/embed.blade.php`.
- [x] 9.2 Apply the `theme_mode` (`light` or `dark`) and `background_color` via inline styles on the wrapper.
- [x] 9.3 Render the layout (`masonry` or `carousel`) by including `partials.embed-masonry` or `partials.embed-carousel`.
- [x] 9.4 Apply `animation_enabled` by adding a CSS class; the actual animation CSS lives in the partials.
- [x] 9.5 The view MUST NOT include:
  - Any links to `/dashboard`, `/login`, `/register`, or any authenticated route.
  - Any JavaScript that fetches authenticated endpoints.
  - Any navigation chrome (sidebar, header, footer).
- [ ] 9.6 Verify in a browser: the iframe content matches the configured layout and theme, testimonials render with submitter name and text, no navigation is present, and a testimonial with `consent_state = 'declined'` is not displayed even if `is_wall_of_love = true`.

> File written: `resources/views/public/embed.blade.php`

## 10. End-to-End Verification

- [ ] 10.1 Run the full happy path in a browser:
  1. Owner creates a space via the dashboard (or a temporary script) and gets the public URL.
  2. Owner enables fields, rating, and the WoL consent.
  3. Visitor opens `/c/{slug}`, fills the form, submits.
  4. Visitor is redirected to `/c/{slug}?submitted=1` with the friendly confirmation.
  5. Owner sees the new testimonial in the inbox (the inbox view comes in a later change; verify via `tinker` or DB query for now).
  6. Owner marks the testimonial as `is_wall_of_love = true`.
  7. Owner visits the embed page; the new testimonial appears in the layout.
- [ ] 10.2 Run the unhappy paths:
  - Submission at the per-space limit is rejected.
  - Submission with a duplicate email is rejected.
  - Submission with a mismatched `public_token` is rejected.
  - Submission without `consent_state` is rejected (handled by the form request).
- [ ] 10.3 Verify the consent invariant end-to-end:
  - Insert a testimonial with `consent_state = 'declined'` directly via SQL.
  - Mark `is_wall_of_love = true` via the model: this should succeed (the DB allows it).
  - The embed widget does NOT display it (the controller filters on `consent_state = 'granted'`).
  - This proves the data-model §6 invariant is enforced *at the read path*, not the write path.

## 11. Documentation Cross-Check

- [ ] 11.1 Compare the controllers and views against `docs/data-model.md` §2 (invariants), §6 (public collection page), §7 (consent invariant), §8 (embed widget) line-by-line. Any deviation is a bug.
- [ ] 11.2 Note any deferred decisions in `docs/data-model.md` §10.
