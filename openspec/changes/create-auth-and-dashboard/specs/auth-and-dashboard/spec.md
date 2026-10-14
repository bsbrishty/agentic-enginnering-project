# Capability: auth-and-dashboard

The authenticated owner-facing surface of the Testimonial SaaS — registration, login, dashboard, spaces CRUD, testimonial inbox, embed builder, and the billing page. After this change archives, the spec merges into `openspec/specs/auth-and-dashboard/spec.md`.

## ADDED Requirements

### Requirement: Authentication scaffold

The system SHALL expose the standard Laravel auth routes at `/login`, `/register`, `/forgot-password`, `/reset-password`, and `POST /logout`. The routes are defined in `routes/auth.php` and loaded from `routes/web.php` via `require`.

The system SHALL provide the following auth controllers in `App\Http\Controllers\Auth`:

- `RegisteredUserController` — `create()`, `store()`.
- `AuthenticatedSessionController` — `create()`, `store()`, `destroy()`.
- `PasswordResetLinkController` — `create()`, `store()`.
- `NewPasswordController` — `create()`, `store()`.

The `User` model created in `create-eloquent-models` (with the `Billable` trait and Cashier columns) is the auth user model. No subclassing or replacement.

#### Scenario: User can register

- **WHEN** an unauthenticated visitor submits the registration form with a valid name, email, and password
- **THEN** a new `User` row is created
- **AND** the user is automatically logged in
- **AND** the user is redirected to the dashboard

#### Scenario: User can log in

- **WHEN** an unauthenticated visitor submits the login form with valid credentials
- **THEN** the user is logged in
- **AND** redirected to the dashboard

#### Scenario: User can log out

- **WHEN** an authenticated user submits the logout form
- **THEN** the user is logged out
- **AND** redirected to the home page or login

#### Scenario: Password reset email is sent

- **WHEN** an unauthenticated visitor submits the forgot-password form with a known email
- **THEN** a password reset email is sent (via Laravel's `Password` broker)
- **AND** the form displays a confirmation message

#### Scenario: Password can be reset

- **WHEN** a visitor clicks the link in the reset email and submits a new password
- **THEN** the user's password is updated
- **AND** the user is logged in

#### Scenario: Authenticated users are redirected from auth pages

- **WHEN** an authenticated user visits `/login` or `/register`
- **THEN** they are redirected to the dashboard

### Requirement: Dashboard home shows owner statistics and quick links

The system SHALL expose `GET /dashboard` resolving to `App\Http\Controllers\Dashboard\DashboardController@index`, protected by the `auth` middleware.

The controller SHALL gather and pass to the view:

- `totalTestimonials` — the count of all testimonials across the owner's spaces.
- `uniqueSubmitters` — the count of distinct `submitter_email_normalized` values across the owner's spaces (queried via raw SQL or a subquery since the column is generated).
- `activeSpaces` — the count of spaces owned by the user.
- `currentPlan` — `Plan::Free` or `Plan::Pro` from `PlanResolver::resolve($user)`.
- `spaceUsage` — `{used: int, max: int}`.
- `testimonialUsage` — `{used: int, max: int}` summed across the owner's spaces.
- `recentTestimonials` — the 5 most recent testimonials across the owner's spaces, with submitter name and space title.

The view SHALL render four stat cards, the recent testimonials list, the plan/usage block, an upgrade banner if the user is on the free plan, and quick links to create a space, open the inbox, and manage embeds.

#### Scenario: Dashboard renders for a logged-in user

- **WHEN** an authenticated user visits `/dashboard`
- **THEN** the response is HTTP 200
- **AND** the response body contains the stat cards, recent testimonials, and quick links

#### Scenario: Unauthenticated users are redirected to login

- **WHEN** an unauthenticated visitor visits `/dashboard`
- **THEN** they are redirected to `/login`

#### Scenario: Free user sees upgrade banner

- **WHEN** the user is on the Free plan
- **THEN** the dashboard view contains the upgrade banner with a link to `/dashboard/billing`

#### Scenario: Pro user does not see upgrade banner

- **WHEN** the user is on the Pro plan
- **THEN** the dashboard view does NOT contain the upgrade banner

#### Scenario: Statistics match the database

- **WHEN** a user has 3 spaces, 250 testimonials, 80 unique submitter emails
- **THEN** the dashboard shows `activeSpaces = 3`, `totalTestimonials = 250`, `uniqueSubmitters = 80`

### Requirement: Space CRUD under /dashboard/spaces

The system SHALL expose a Laravel resource route `Route::resource('dashboard/spaces', Dashboard\SpaceController::class)`, except that `show` resolves at `dashboard/spaces/{space}` for the public-URL display page. All routes are protected by the `auth` middleware and the `SpacePolicy`.

The `SpaceController` (dashboard namespace) SHALL provide:

- `index()` — list the owner's spaces.
- `create()` — show the create form.
- `store(StoreSpaceRequest $request)` — call `SpaceService::create($request->user(), $request->validated())`. On `SpaceLimitReachedException`, redirect back with a flash error. On success, redirect to the new space's show page with a flash success.
- `show(Space $space)` — show counts, embed configs, public URL with copy button.
- `edit(Space $space)` — show the edit form, prefilled.
- `update(UpdateSpaceRequest $request, Space $space)` — call `SpaceService::update($space, $request->validated())`. On success, redirect back with a flash success.
- `destroy(Space $space)` — delete the space (cascades to testimonials and embed configs). Redirect to `/dashboard/spaces` with a flash success.

The `SpacePolicy::view`, `update`, `delete` methods SHALL all return `$user->id === $space->user_id`.

#### Scenario: Owner can create a space

- **WHEN** an authenticated owner submits the create form with valid fields
- **AND** they are under the plan's space limit
- **THEN** a new `Space` row is created
- **AND** `public_slug` and `public_token` are auto-generated
- **AND** the user is redirected to `/dashboard/spaces/{slug}`

#### Scenario: Owner cannot create beyond plan limit

- **WHEN** an authenticated free-plan owner (limit 3) attempts to create a 4th space
- **THEN** the form submission fails with a friendly error
- **AND** no new `Space` row is created

#### Scenario: Owner can edit a space

- **WHEN** an authenticated owner submits the edit form for one of their spaces
- **THEN** the space's editable fields are updated
- **AND** `public_slug` and `public_token` are unchanged

#### Scenario: Owner can delete a space

- **WHEN** an authenticated owner submits the delete form for one of their spaces
- **THEN** the space is removed
- **AND** all testimonials and embed configs for that space are removed via cascade
- **AND** the user is redirected to `/dashboard/spaces`

#### Scenario: Non-owner cannot edit a space

- **WHEN** user A attempts to access `/dashboard/spaces/{slug-of-B}/edit`
- **THEN** the response is HTTP 403

#### Scenario: Public URL is displayed with copy button

- **WHEN** the owner views `/dashboard/spaces/{slug}`
- **THEN** the page displays the full public URL (`/c/{slug}`)
- **AND** includes a button that copies it to the clipboard (handled client-side)

### Requirement: Testimonial inbox with filters and search

The system SHALL expose `GET /dashboard/inbox` resolving to `TestimonialInboxController@index`, protected by the `auth` middleware.

The controller SHALL delegate to `InboxService::query($user, $filters)`, which:

- Scopes the query to spaces owned by `$user`.
- Applies optional filters: `space_id`, `rating` (exact), `consent_state`, `is_favorite`, `is_wall_of_love`, `is_hidden`, date range.
- Applies optional `q` search: `MATCH(testimonial_text) AGAINST(? IN NATURAL LANGUAGE MODE)` for long-form search, AND prefix `LIKE` on `submitter_name` and `submitter_email`.
- Paginates 25 per page, ordered by `created_at DESC`.

The view SHALL render a filter form (space dropdown, status checkboxes, date range, search input), the paginated list, and per-row action buttons (favorite, WoL, hide, edit, delete).

#### Scenario: Inbox shows all owner's testimonials paginated

- **WHEN** the owner has 30 testimonials
- **THEN** the inbox shows 25 on the first page with pagination links

#### Scenario: Filter by space

- **WHEN** the owner has spaces S1 and S2
- **AND** selects `space_id = S1` in the filter
- **THEN** only S1's testimonials are shown

#### Scenario: Filter by favorites only

- **WHEN** `is_favorite = 1` is in the filter
- **THEN** only favorited testimonials are shown

#### Scenario: Full-text search on testimonial text

- **WHEN** `q = 'excellent service'` is in the filter
- **THEN** the query uses `MATCH(testimonial_text) AGAINST(? IN NATURAL LANGUAGE MODE)`
- **AND** only testimonials containing the phrase are returned

#### Scenario: Prefix search on name or email

- **WHEN** `q = 'jane'` is in the filter
- **THEN** the query includes `submitter_name LIKE 'jane%' OR submitter_email LIKE 'jane%'`

### Requirement: Wall of Love toggle enforces consent invariant

The system SHALL expose `PATCH /dashboard/testimonials/{testimonial}` resolving to `TestimonialInboxController@update`, protected by `auth` and the `TestimonialPolicy`.

The controller SHALL accept these per-row actions in the request body:

- `action=favorite` toggles `is_favorite`.
- `action=wall_of_love` sets `is_wall_of_love = !$testimonial->is_wall_of_love`. **BEFORE persisting the new value of `true`, the controller MUST call `ConsentGuard::assertCanPublish($testimonial)`. If the guard throws, the controller returns HTTP 403 with a friendly message and does not persist.**
- `action=hidden` toggles `is_hidden`.
- `action=edit` updates `testimonial_text` and other editable fields from the request body.

The `action` parameter is required; unknown actions return 422.

#### Scenario: Owner can favorite a testimonial

- **WHEN** the owner POSTs `action=favorite` for a testimonial
- **THEN** the `is_favorite` field is toggled

#### Scenario: Owner cannot Wall of Love a declined testimonial

- **WHEN** the owner POSTs `action=wall_of_love` for a testimonial with `consent_state = 'declined'`
- **AND** the current `is_wall_of_love` value is `false` (so the toggle would set it to `true`)
- **THEN** `ConsentGuard::assertCanPublish()` throws
- **AND** the controller returns HTTP 403 with a "this testimonial does not have consent to be published" message
- **AND** the `is_wall_of_love` field is NOT changed

#### Scenario: Owner can Wall of Love a granted testimonial

- **WHEN** the owner POSTs `action=wall_of_love` for a testimonial with `consent_state = 'granted'`
- **THEN** the `is_wall_of_love` field is toggled to `true`

#### Scenario: Owner can un-Wall-of-Love a hidden testimonial

- **WHEN** the owner POSTs `action=wall_of_love` for a testimonial currently with `is_wall_of_love = true`
- **THEN** the `is_wall_of_love` field is toggled to `false`
- **NOTE** the consent guard only fires when toggling TO true, not when toggling to false. A previously-WoL'd testimonial can always be un-WoL'd, even if the customer later revoked consent.

#### Scenario: Owner can edit testimonial text

- **WHEN** the owner POSTs `action=edit` with `testimonial_text = 'New text'`
- **THEN** the testimonial's text is updated

#### Scenario: Owner can hide a testimonial

- **WHEN** the owner POSTs `action=hidden` for a testimonial
- **THEN** the `is_hidden` field is toggled

#### Scenario: Non-owner cannot update a testimonial

- **WHEN** user A attempts to PATCH user B's testimonial
- **THEN** the response is HTTP 403

### Requirement: Testimonial deletion

The system SHALL expose `DELETE /dashboard/testimonials/{testimonial}` resolving to `TestimonialInboxController@destroy`, protected by `auth` and the `TestimonialPolicy`.

The controller SHALL delete the testimonial and redirect to `/dashboard/inbox` with a flash success. The view's delete button SHALL be inside a confirmation modal (per PRD §13).

#### Scenario: Owner can delete a testimonial

- **WHEN** the owner submits the delete form for a testimonial
- **THEN** the testimonial row is removed
- **AND** the user is redirected to the inbox

### Requirement: Embed builder with live preview

The system SHALL expose nested resource routes for `EmbedConfig` under `dashboard/spaces/{space}/embeds`, resolving to `Dashboard\EmbedConfigController`. The routes are protected by `auth` and the `SpacePolicy`.

The controller SHALL provide:

- `index(Space $space)` — list the space's embed configs with their copyable snippets.
- `create(Space $space)` — show the embed builder form (empty).
- `store(StoreEmbedConfigRequest $request, Space $space)` — create the embed config; `public_id` is auto-generated.
- `edit(Space $space, EmbedConfig $embedConfig)` — show the embed builder prefilled.
- `update(Request $request, Space $space, EmbedConfig $embedConfig)` — update the embed config (no separate form request; the same column rules apply).
- `destroy(Space $space, EmbedConfig $embedConfig)` — delete the embed config.

The create and edit views SHALL include a live preview pane that renders an `<iframe>` pointing at `/embed/{preview_public_id_or_seed}`. Because the embed config's `public_id` is generated on save, the preview iframe during creation uses a temporary seed (e.g., a `data-` attribute set from the form values) or a query parameter. The MVP approach: the preview iframe source is rebuilt on form change via JS, pointing at `/embed/preview?layout=...&theme_mode=...&background_color=...` — but the preview endpoint is `create-stripe-billing`-era work. For this change, the preview iframe shows the *current* form values via a small inline JS renderer that mimics the embed view; a real preview route is deferred.

The `index` view SHALL show each embed config with:

- Its public URL: `/embed/{public_id}`.
- A copyable JavaScript snippet: `<iframe src="{absolute_url}/embed/{public_id}" width="100%" height="600" frameborder="0"></iframe>`.
- A "Copy snippet" button.

#### Scenario: Owner can create an embed config

- **WHEN** the owner submits the embed builder form for a space they own
- **THEN** a new `EmbedConfig` row is created
- **AND** `public_id` is auto-generated as a UUID v4

#### Scenario: Owner can edit an embed config

- **WHEN** the owner submits the edit form for an embed config
- **THEN** the layout, theme_mode, background_color, and animation_enabled fields are updated
- **AND** `public_id` is unchanged

#### Scenario: Owner can copy a snippet

- **WHEN** the owner views the embed config list
- **THEN** each row displays the iframe snippet
- **AND** a "Copy" button copies the snippet to the clipboard

#### Scenario: Non-owner cannot manage embeds

- **WHEN** user A attempts to access the embed builder for user B's space
- **THEN** the response is HTTP 403

### Requirement: Billing page shows current plan and usage

The system SHALL expose `GET /dashboard/billing` resolving to `Dashboard\BillingController@index`, protected by `auth`.

The controller SHALL show:

- The current plan (Free or Pro) and its limits.
- Space usage: `{used} / {max}`.
- Testimonial usage: `{used} / {max}` summed across spaces.
- The available features for the current plan.
- An "Upgrade" button for free users linking to `/dashboard/billing/checkout` (which is a 404 in this change; the Stripe Checkout integration is `create-stripe-billing`).

#### Scenario: Free user sees upgrade button

- **WHEN** a free user visits `/dashboard/billing`
- **THEN** the upgrade button is visible
- **AND** the button links to `/dashboard/billing/checkout`

#### Scenario: Pro user does not see upgrade button

- **WHEN** a pro user visits `/dashboard/billing`
- **THEN** the upgrade button is NOT visible
- **AND** the page shows "You're on Pro" messaging

### Requirement: Dashboard routes are protected and isolated

All `/dashboard/*` routes SHALL be inside a route group with:

- `prefix = 'dashboard'`
- `middleware = ['auth']`
- A named-route prefix of `dashboard.`

The `auth` middleware SHALL redirect unauthenticated visitors to `/login`.

A user SHALL NOT be able to view, edit, or delete another user's space, testimonial, or embed config. The `SpacePolicy` and `TestimonialPolicy` enforce this; the controllers call `Gate::authorize(...)` (or `$this->authorize(...)`) before any state-changing action.

#### Scenario: Unauthenticated dashboard access redirects to login

- **WHEN** an unauthenticated visitor visits any `/dashboard/*` route
- **THEN** they are redirected to `/login`
- **AND** the original URL is preserved as the intended redirect

#### Scenario: Cross-tenant space access is forbidden

- **WHEN** user A is logged in and visits user B's space via `/dashboard/spaces/{slug-of-B}/edit`
- **THEN** the response is HTTP 403
- **AND** no space data is leaked in the response body

#### Scenario: Cross-tenant testimonial access is forbidden

- **WHEN** user A attempts to PATCH or DELETE user B's testimonial
- **THEN** the response is HTTP 403

### Requirement: Auth pages use a guest layout, dashboard uses the dashboard layout

The `auth/*` Blade views SHALL extend `layouts.guest` (no sidebar, no auth navigation).

The `dashboard/*` Blade views SHALL extend `layouts.dashboard` (sidebar with navigation: Dashboard, Spaces, Inbox, Embeds, Billing; top bar with user menu and logout).

The dashboard sidebar SHALL highlight the active section based on the current route name.

#### Scenario: Auth pages have no dashboard navigation

- **WHEN** a visitor views `/login`
- **THEN** the rendered HTML does NOT contain links to `/dashboard`
- **AND** the page does not show the sidebar

#### Scenario: Dashboard pages have sidebar navigation

- **WHEN** an authenticated user views `/dashboard/spaces`
- **THEN** the rendered HTML contains a sidebar with links to `/dashboard`, `/dashboard/spaces`, `/dashboard/inbox`, `/dashboard/billing`
- **AND** the "Spaces" link is visually marked as active

#### Scenario: Logout link is in the dashboard layout

- **WHEN** an authenticated user views any dashboard page
- **THEN** the top bar contains a logout form
- **AND** submitting it logs the user out
