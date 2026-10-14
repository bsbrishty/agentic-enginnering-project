# Proposal: Create Form Request Validation Classes

## Why

The data-model spec in `docs/data-model.md` §8 enumerates the column-level validation rules for every entity. Without Form Request classes, that contract is unenforceable — controllers would have to repeat the rules inline, validators would diverge, and the consent invariant (PRD §7) and email dedup (data-model §2) would have no first-class expression in the application layer. This change consolidates the column-level validation contract into Laravel 11 Form Request classes so every controller, route, or action that accepts user input gets the same validation behavior.

This change covers **column-level** validation only. Cross-cutting business rules (plan-limit enforcement, the consent invariant, the "rating only valid when `rating_enabled`" rule) are enforced at the policy or service layer and are explicitly out of scope here.

## What

Create four Form Request classes under `app/Http/Requests/`:

1. **`StoreSpaceRequest`** — validates a request to create a new `Space`. Rules per `docs/data-model.md` §8.1.
2. **`UpdateSpaceRequest`** — validates a request to update an existing `Space`. Same column rules as `StoreSpaceRequest`, but with `user_id` removed from the fillable set (ownership is not transferable) and `public_slug` removed (URL is immutable after creation per Round 1 Q4).
3. **`StoreTestimonialRequest`** — validates the public submission form. Rules per `docs/data-model.md` §8.2. Allows unauthenticated callers (no `authorize()` gate that requires a logged-in user; the gate instead checks that the space accepts submissions and that the request is not from an internal admin endpoint).
4. **`StoreEmbedConfigRequest`** — validates a request to create a new `EmbedConfig`. Rules per `docs/data-model.md` §8.3.

Each class extends `Illuminate\Foundation\Http\FormRequest`, implements `authorize()` returning `true` (the route's middleware/policy handles access control, not the form request), and provides a `rules()` method returning the rule set as an array. The `messages()` and `attributes()` methods are not overridden in this change — Laravel's defaults are sufficient for the MVP.

The `UpdateSpaceRequest` adds `sometimes` to all rules (so partial updates don't fail on missing fields) and explicitly removes the `public_slug` rule.

## Out of Scope

- Policies (`SpacePolicy`, `EmbedConfigPolicy`) — these gate who can call the form-request endpoints and are a separate change.
- The `Plan` enum and `PlanResolver` service.
- The Stripe webhook handler.
- Custom error messages, attribute translations, or locale-specific validation messages.
- File-upload validation for `testimonials.profile_photo_url` (the URL itself is validated as a string; the upload pipeline is a separate concern that produces a URL).
- API resource classes (transformers for JSON responses).

## Affected Capabilities

- `form-requests` — new capability spec, added by this change.

## Dependencies

- `create-eloquent-models` (applied) — the form requests validate the *attributes* of these models, so the model classes must exist for the validation contract to be coherent.
- `create-database-migrations` (applied) — the schema's NOT NULL constraints backstop the form-request `required` rules at the DB level.

## Risks

- **`required` vs `nullable` drift.** A `required` rule on a column that's nullable in the DB is a valid choice (the form refuses to submit a null) but must be tracked. This change follows the data-model spec exactly; the spec is the source of truth.
- **`public_slug` immutability.** The Round 1 decision that slugs are immutable after creation means `UpdateSpaceRequest` must NOT include a `public_slug` rule. If a controller accepts the field and forwards it to the model, the slug would change. This risk is documented in the spec and in the `UpdateSpaceRequest` docblock.
- **JSON validation depth.** The `fields_config` field is a JSON array of objects. Validating the structure of each object (the `key`, `type`, `enabled`, `required` shape per data-model §3.2.1) is technically possible with nested `array` rules but bloats the request class. This change validates the *outer* shape (`array`, non-empty) and defers the per-field structural validation to a future change if needed. The doc explicitly marks this as a deferred decision.
- **`rating` is conditional.** The rule "rating required when `rating_enabled` is true" is a cross-field rule and lives at the service/policy layer, not in the form request. The form request validates `rating` as `nullable|integer|min:1|max:5`; the controller-level or policy-level check enforces the conditional.
- **No auth gate in `authorize()`.** Returning `true` here means the route's middleware (e.g., `auth`, `verified`) is the only access gate at this layer. If a future change adds per-route authorization, that should be expressed via a Policy class with `authorize()` returning the policy result, not by editing these form requests.
