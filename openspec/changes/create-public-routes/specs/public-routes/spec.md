# Capability: public-routes

The public surface of the Testimonial SaaS — the unauthenticated collection form, the submission endpoint, and the embed widget iframe. After this change archives, the spec merges into `openspec/specs/public-routes/spec.md`.

## ADDED Requirements

### Requirement: Public collection form renders a space by slug

The system SHALL expose `GET /c/{slug}` resolving to `App\Http\Controllers\Public\SpaceController@show`.

The controller SHALL resolve the `Space` model via route model binding keyed on `public_slug`. If no space matches the slug, the system SHALL return HTTP 404.

The controller SHALL return the `public.space` Blade view with the resolved `$space` and a `submitted` flash flag derived from the `submitted` query string parameter.

The view SHALL render the collection form with:

- The space's `title`, `subtitle`, and `collection_question`.
- An input for each entry in `$space->fields_config` where `enabled === true`. Required fields SHALL be marked with the HTML `required` attribute and visually indicated.
- A `testimonial_text` field rendered as `<textarea>` when the `testimonial_text` entry is enabled.
- A `profile_photo_url` field rendered as a `<input type="url">` when the `profile_photo` entry is enabled.
- A rating input (1–5 stars) when `$space->rating_enabled` is true.
- A consent checkbox that submits `consent_state` as either `'granted'` or `'declined'`.
- A hidden `public_token` field with the value of `$space->public_token`.
- A submit button labeled appropriately.

The form SHALL post to `POST /c/{slug}` (same URL).

#### Scenario: GET /c/{slug} renders the form for a known space

- **WHEN** a visitor requests `GET /c/acme-co-abc123` and a space with `public_slug = 'acme-co-abc123'` exists
- **THEN** the response is HTTP 200
- **AND** the response body contains the form
- **AND** the form's `action` attribute is `/c/acme-co-abc123`
- **AND** the form contains a hidden `public_token` input with the space's `public_token`

#### Scenario: GET /c/{slug} returns 404 for unknown space

- **WHEN** a visitor requests `GET /c/does-not-exist`
- **THEN** the response is HTTP 404

#### Scenario: Required fields are visually marked

- **WHEN** a space has `fields_config` with `name` and `email` set to `required: true`
- **THEN** the rendered form has `required` on the `name` and `email` inputs
- **AND** the visual indicator (asterisk or equivalent) is present

#### Scenario: Success state replaces the form

- **WHEN** a visitor requests `GET /c/acme-co-abc123?submitted=1` after a successful POST
- **THEN** the form is NOT rendered
- **AND** the friendly "You're officially awesome! 🎉" message IS rendered
- **AND** no `public_token` hidden field is present (no risk of replay)

### Requirement: Submission endpoint validates and persists a testimonial

The system SHALL expose `POST /c/{slug}` resolving to `Public\SpaceController@store`, protected by the `throttle:public-submission` middleware and validated by `StoreTestimonialRequest`.

The controller SHALL:

1. Resolve the `Space` via route model binding by `public_slug`.
2. Verify that `$request->input('public_token') === $space->public_token`. If not, abort with HTTP 403.
3. Determine the owner's plan via `PlanResolver::resolve($space->owner)`.
4. Count `$space->testimonials()->count()`. If the count is at or above the plan's per-space limit, abort with HTTP 422 and a "limit reached" error.
5. Create the testimonial via `$space->testimonials()->create($request->validated() + ['submitted_at' => now()])`.
6. Catch `QueryException` with SQLSTATE `23000` (unique violation) and re-throw as HTTP 422 with a "duplicate submission" error.
7. Redirect to `/c/{slug}?submitted=1` on success.

The `StoreTestimonialRequest` is the validation contract; the controller relies on it for field-level rules.

#### Scenario: Valid submission is accepted

- **WHEN** a POST is made to `/c/{slug}` with a complete valid payload (including `consent_state` and matching `public_token`)
- **THEN** a new `Testimonial` row is created linked to the space
- **AND** the response is HTTP 302 to `/c/{slug}?submitted=1`
- **AND** the row's `submitted_at` is approximately `now()`

#### Scenario: Mismatched public token is rejected

- **WHEN** a POST is made to `/c/{slug}` with a `public_token` that does not match the space's `public_token`
- **THEN** the response is HTTP 403
- **AND** no `Testimonial` row is created

#### Scenario: Submission at plan limit is rejected

- **WHEN** a space has 100 testimonials and the owner is on the Free plan
- **AND** a POST is made to `/c/{slug}` with a valid payload
- **THEN** the response is HTTP 422 with an error indicating the per-space limit
- **AND** no `Testimonial` row is created

#### Scenario: Duplicate email is rejected

- **WHEN** a space has a testimonial with `submitter_email = 'jane@example.com'`
- **AND** a POST is made to `/c/{slug}` with `submitter_email = 'JANE@example.com'`
- **THEN** the response is HTTP 422 with a duplicate-submission error
- **AND** no second `Testimonial` row is created

#### Scenario: Validation errors return 422

- **WHEN** a POST is made to `/c/{slug}` with `testimonial_text` missing
- **THEN** the response is HTTP 422
- **AND** the response body contains a `testimonial_text` error
- **AND** no `Testimonial` row is created

### Requirement: Embed widget renders only eligible testimonials

The system SHALL expose `GET /embed/{public_id}` resolving to `Public\EmbedController@show`, with no authentication required (the route is loaded inside an iframe on third-party sites).

The controller SHALL:

1. Resolve the `EmbedConfig` via route model binding by `public_id`. If no match, return HTTP 404.
2. Load the parent `$space = $embedConfig->space`.
3. Query the eligible testimonials: `space_id = $space->id AND is_wall_of_love = 1 AND is_hidden = 0 AND consent_state = 'granted'`, ordered by `created_at DESC`.
4. Return the `public.embed` Blade view with `$embedConfig`, `$space`, and `$testimonials`.
5. Set `Cache-Control: public, max-age=60` to limit how often third-party sites hit the DB.

The query at step 3 is the *single source of truth* for the consent invariant (data-model §6). The DB does not enforce it; the read path filters it.

#### Scenario: Embed widget returns 404 for unknown public id

- **WHEN** a visitor (or a third-party iframe) requests `GET /embed/00000000-0000-0000-0000-000000000000`
- **AND** no `EmbedConfig` has that `public_id`
- **THEN** the response is HTTP 404

#### Scenario: Embed widget returns only eligible testimonials

- **WHEN** a space has three testimonials:
  - `T1`: `is_wall_of_love = 1, is_hidden = 0, consent_state = 'granted'`
  - `T2`: `is_wall_of_love = 1, is_hidden = 0, consent_state = 'declined'`
  - `T3`: `is_wall_of_love = 0, is_hidden = 0, consent_state = 'granted'`
- **AND** `GET /embed/{public_id}` is requested
- **THEN** the response includes only `T1`
- **AND** `T2` and `T3` are not in the response

#### Scenario: Embed widget respects hidden flag

- **WHEN** a space has a testimonial with `is_wall_of_love = 1, is_hidden = 1, consent_state = 'granted'`
- **THEN** the embed widget does NOT include it

#### Scenario: Embed widget is publicly cacheable for 60 seconds

- **WHEN** `GET /embed/{public_id}` returns successfully
- **THEN** the response includes a `Cache-Control` header allowing public caching for up to 60 seconds

### Requirement: Embed widget view has no authenticated surface

The `public.embed` Blade view SHALL NOT include:

- Any `<a href="...">` link to a URL matching `/dashboard`, `/login`, `/register`, `/billing`, `/spaces/*/edit`, or any other authenticated route.
- Any `<script>` that fetches an authenticated endpoint.
- Any navigation chrome (sidebar, header bar, footer with auth links).
- Any form that posts to an authenticated endpoint.

The view SHALL include:

- The testimonials rendered in the chosen layout (`masonry` or `carousel`).
- The chosen `theme_mode` (`light` or `dark`) applied as a class or inline style on the wrapper.
- The `background_color` applied as an inline `background-color` style on the wrapper.
- An `animation_enabled` class on the wrapper when `$embedConfig->animation_enabled` is true.

#### Scenario: Embed view has no links to authenticated routes

- **WHEN** the embed view is rendered for any `EmbedConfig`
- **THEN** a string search of the rendered HTML does NOT find `href="/dashboard"`, `href="/login"`, `href="/register"`, `href="/billing"`, or `href="/spaces/`
- **AND** a string search does NOT find any `<script>` tag with a `fetch(` or `XMLHttpRequest` to an authenticated URL

#### Scenario: Embed view applies the configured theme and color

- **WHEN** an `EmbedConfig` has `theme_mode = 'dark'` and `background_color = '#1a1a1a'`
- **THEN** the rendered HTML includes a wrapper element with a class indicating dark mode
- **AND** the wrapper has an inline `background-color: #1a1a1a` (or equivalent) style

#### Scenario: Embed view applies the configured layout

- **WHEN** an `EmbedConfig` has `layout = 'masonry'`
- **THEN** the rendered HTML includes the masonry layout markup
- **AND** it does NOT include the carousel layout markup

### Requirement: Submission and embed flows are publicly accessible

The public routes SHALL NOT require authentication. No `auth`, `verified`, or signed middleware SHALL be applied to:

- `GET /c/{slug}`
- `POST /c/{slug}`
- `GET /embed/{public_id}`

The `POST /c/{slug}` route SHALL apply the `throttle:public-submission` middleware to limit request rate. The exact rate is configured elsewhere; this change wires the middleware name in.

#### Scenario: Public routes do not redirect to login

- **WHEN** an unauthenticated visitor makes a GET or POST to any of the three public routes
- **THEN** the response is NOT a 302 to `/login`
- **AND** the request is processed normally

#### Scenario: Public submission is rate-limited

- **WHEN** more than N requests are made to `POST /c/{slug}` from the same IP within the configured window
- **THEN** the (N+1)th request returns HTTP 429
- **AND** no `Testimonial` row is created

### Requirement: Consent invariant is enforced at the read path

The consent invariant (data-model §6) requires that a testimonial appear in public embeds only if `consent_state = 'granted'` AND `is_wall_of_love = true` AND `is_hidden = false`. The system SHALL enforce this invariant at the read path (the embed query) AND at any application-level publish action.

The `App\Services\ConsentGuard::assertCanPublish(Testimonial $t)` method SHALL throw `ConsentViolationException` if `$t->consent_state === 'declined'`. This is the application-level check used by the dashboard's "mark as Wall of Love" action (a future change) and any other code path that sets `is_wall_of_love = true` on an existing testimonial.

The DB does NOT enforce this; the `testimonials` table allows any value of `is_wall_of_love` regardless of `consent_state`. The application layer is responsible.

#### Scenario: Declined consent blocks public display

- **WHEN** a testimonial has `consent_state = 'declined'` and `is_wall_of_love = 1`
- **THEN** the embed widget does NOT include it (the read-path filter)

#### Scenario: ConsentGuard rejects publish on declined

- **WHEN** `ConsentGuard::assertCanPublish($t)` is called on a testimonial with `consent_state = 'declined'`
- **THEN** `ConsentViolationException` is thrown
- **AND** the test calling the method sees the exception

#### Scenario: ConsentGuard allows publish on granted

- **WHEN** `ConsentGuard::assertCanPublish($t)` is called on a testimonial with `consent_state = 'granted'`
- **THEN** the method returns normally

#### Scenario: ConsentGuard rejects publish on not_asked

- **WHEN** `ConsentGuard::assertCanPublish($t)` is called on a testimonial with `consent_state = 'not_asked'`
- **THEN** `ConsentViolationException` is thrown
- **NOTE** "not_asked" must be re-confirmed by the submitter before being eligible for the Wall of Love; the application cannot promote it.

### Requirement: PlanResolver derives effective plan from subscriptions

The `App\Services\PlanResolver::resolve(User $user): Plan` method SHALL return `Plan::Pro` if the user has any `Subscription` row where `stripe_status` is in `['active', 'trialing', 'past_due']` AND (`ends_at IS NULL` OR `ends_at > now()`). Otherwise, return `Plan::Free`.

The plan limits SHALL be defined as constants on the `Plan` enum:

- `Plan::Free`: `maxSpaces = 3`, `maxTestimonialsPerSpace = 100`
- `Plan::Pro`: `maxSpaces = 25`, `maxTestimonialsPerSpace = 1000`

#### Scenario: User with no subscription is free

- **WHEN** a user has no `subscriptions` rows
- **THEN** `PlanResolver::resolve($user)` returns `Plan::Free`

#### Scenario: User with active subscription is pro

- **WHEN** a user has a `subscriptions` row with `stripe_status = 'active'` and `ends_at IS NULL`
- **THEN** `PlanResolver::resolve($user)` returns `Plan::Pro`

#### Scenario: User with past_due is still pro

- **WHEN** a user has a `subscriptions` row with `stripe_status = 'past_due'` and `ends_at > now()`
- **THEN** `PlanResolver::resolve($user)` returns `Plan::Pro`

#### Scenario: User with canceled subscription is free

- **WHEN** a user has a `subscriptions` row with `stripe_status = 'canceled'`
- **THEN** `PlanResolver::resolve($user)` returns `Plan::Free`

#### Scenario: User with past ends_at is free

- **WHEN** a user has a `subscriptions` row with `stripe_status = 'active'` but `ends_at < now()`
- **THEN** `PlanResolver::resolve($user)` returns `Plan::Free`

### Requirement: Public routes do not leak dashboard data

The public collection form and embed widget SHALL NOT include any of the following in their rendered HTML or response headers:

- The owner's `name`, `email`, or `stripe_id`.
- The owner's other spaces, testimonials, or embed configs.
- Any internal `id` (auto-increment) values.
- The `public_token` is included in the collection form (as a hidden field) but NOT in the embed widget.
- The `submitter_email_normalized` generated column is NEVER exposed to the client.

#### Scenario: Public collection form does not include owner email

- **WHEN** `GET /c/{slug}` is rendered for a space owned by `jane@example.com`
- **THEN** the rendered HTML does NOT contain `jane@example.com`

#### Scenario: Embed widget does not include other spaces' testimonials

- **WHEN** an embed is rendered for a space with 5 testimonials
- **AND** the owner has another space with 10 testimonials
- **THEN** the rendered HTML contains only the 5 testimonials from the first space
- **AND** the 10 from the second space are not present

#### Scenario: Embed widget does not expose public_token

- **WHEN** `GET /embed/{public_id}` is rendered
- **THEN** the rendered HTML does NOT contain the value of the parent space's `public_token`
