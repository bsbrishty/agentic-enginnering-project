{{--
    Public collection form for a Space.
    Per docs/data-model.md §3.2 + §6 and PRD §5 + §6.

    - Title, subtitle, and collection question are rendered from $space.
    - Each enabled entry in fields_config renders an input.
    - Required fields are marked with the HTML `required` attribute and a
      visual asterisk.
    - The consent checkbox is mandatory (two radio inputs so the form can
      express explicit refusal, per data-model §6).
    - A hidden `public_token` is required for submission.
    - When `?submitted=1` is in the query string, the form is replaced
      with a friendly confirmation (PRD §6: "You're officially awesome!").
    - The accessor `fields_config_resolved` is used to guarantee a
      non-empty field list even if the column is NULL or `[]`.

    Modernization (PRD §13):
    - Greeting emojis, gradient card, modern form spacing.
    - Interactive star rating picker (1–5) with hover preview, hidden
      behind a real <select> for accessibility & form submission.
    - Success page runs canvas-confetti for a celebratory moment.
--}}

@php
    $fieldsConfig = $space->fields_config_resolved;
    $ratingEnabled = (bool) ($space->star_ratings_enabled ?? $space->rating_enabled ?? false);
    $isActive = (bool) ($space->is_active ?? true);
    $oldRating = (int) old('rating', 0);
@endphp

@extends('layouts.public')

@section('title', $space->title)

@section('content')
    <style>
        .public-container .space-hero {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .public-container .space-hero .hero-emoji {
            font-size: 2.5rem;
            display: inline-block;
            margin-bottom: 0.5rem;
            filter: drop-shadow(0 4px 8px rgba(22, 119, 255, 0.18));
        }
        .public-container .space-hero h1 { font-size: 2rem; }
        .public-container .space-hero .collection-question {
            color: var(--pub-text-soft);
            font-size: 1.05rem;
            margin: 0.75rem 0 0;
        }

        /* Interactive star rating picker */
        .public-container .rating-picker {
            display: inline-flex;
            gap: 0.2rem;
            font-size: 1.85rem;
            line-height: 1;
            margin-top: 0.5rem;
            user-select: none;
        }
        .public-container .rating-picker .star {
            cursor: pointer;
            color: rgba(156, 163, 175, 0.4);
            transition: color 0.12s ease, transform 0.12s ease;
        }
        .public-container .rating-picker .star.is-on,
        .public-container .rating-picker .star.is-hover {
            color: var(--pub-gold);
            text-shadow: 0 0 8px rgba(251, 191, 36, 0.5);
        }
        .public-container .rating-picker .star:hover { transform: scale(1.15); }
        .public-container .rating-picker .star:focus-visible {
            outline: 2px solid var(--pub-primary);
            outline-offset: 3px;
            border-radius: 4px;
        }
        .public-container .rating-picker .clear {
            font-size: 0.85rem;
            color: var(--pub-text-soft);
            margin-left: 0.75rem;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            text-decoration: underline;
        }
        /* Visually hidden but accessible to assistive tech */
        .public-container .sr-only {
            position: absolute;
            width: 1px; height: 1px;
            padding: 0; margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .public-container .inactive-card {
            text-align: center;
            padding: 2.5rem 1rem;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 14px;
        }
        .public-container .inactive-card h1 {
            font-size: 1.5rem;
            color: #9a3412;
        }
        .public-container .inactive-card p { color: #9a3412; margin: 0.5rem 0 0; }
    </style>

    @if ($submitted)
        <div class="confirmation">
            <div style="font-size: 3rem; margin-bottom: 0.5rem;">🎉✨🥳</div>
            <h1>{{ __('messages.public.confirmation_heading') }}</h1>
            <p>{{ __('messages.public.confirmation_body') }} 💖</p>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js" defer></script>
        <script>
            (function () {
                function fire() {
                    if (typeof confetti !== 'function') { return; }
                    var colors = ['#1677ff', '#ec4899', '#fbbf24', '#10b981', '#8b5cf6'];
                    confetti({ particleCount: 120, spread: 80, origin: { y: 0.55 }, colors: colors });
                    setTimeout(function () {
                        confetti({ particleCount: 80, angle: 60, spread: 65, origin: { x: 0, y: 0.7 }, colors: colors });
                        confetti({ particleCount: 80, angle: 120, spread: 65, origin: { x: 1, y: 0.7 }, colors: colors });
                    }, 250);
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', fire);
                } else {
                    fire();
                }
            })();
        </script>
    @elseif (! $isActive)
        <div class="inactive-card">
            <div style="font-size: 2.5rem;">🌙</div>
            <h1>{{ __('messages.public.inactive_heading') }}</h1>
            <p>{{ __('messages.public.inactive_body') }}</p>
        </div>
    @else
        <div class="space-hero">
            <div class="hero-emoji">💬</div>
            <h1>{{ $space->title }}</h1>
            @if ($space->subtitle)
                <h2>{{ $space->subtitle }}</h2>
            @endif
            <p class="collection-question">{{ $space->collection_question }}</p>
        </div>

        @if ($errors->any())
            <div class="errors">
                <strong>⚠️ Please fix the following:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="/c/{{ $space->public_slug }}">
            @csrf
            <input type="hidden" name="public_token" value="{{ $space->public_token }}">

            {{-- Honeypot field; must remain empty. Hidden from humans via
                 inline styles (not display:none, since bots skip that). --}}
            <div style="position: absolute; left: -10000px; top: auto; width: 1px; height: 1px; overflow: hidden;" aria-hidden="true">
                <label for="website">Website (leave blank)</label>
                <input type="text" name="website" id="website" value="" tabindex="-1" autocomplete="off">
            </div>

            @foreach ($fieldsConfig as $field)
                @if (! is_array($field) || ! ($field['enabled'] ?? false))
                    @continue
                @endif
                @php
                    $key = $field['key'] ?? null;
                    $isRequired = $field['required'] ?? false;
                    $type = $field['type'] ?? 'text';

                    // Decide which input `name` attribute to use. Canonical
                    // keys map to the scalar columns on the testimonials
                    // table; non-canonical keys are captured under
                    // `field_values[<key>]`.
                    $canonicals = [
                        'name' => 'submitter_name',
                        'email' => 'submitter_email',
                        'company' => 'submitter_company',
                        'testimonial_text' => 'testimonial_text',
                        'social_links' => 'submitter_social_links',
                        'profile_photo' => 'profile_photo_url',
                    ];
                    $inputName = $canonicals[$key] ?? null;
                    $isCanonical = $inputName !== null;
                @endphp
                @if (! $key)
                    @continue
                @endif
                <label for="field-{{ $key }}">
                    {{ ucwords(str_replace('_', ' ', $key)) }}
                    @if ($isRequired)<span class="required">*</span>@endif
                </label>
                @if ($type === 'textarea')
                    @if ($isCanonical)
                        <textarea name="{{ $inputName }}" id="field-{{ $key }}"
                            @if ($isRequired) required @endif>{{ old($inputName) }}</textarea>
                    @else
                        <textarea name="field_values[{{ $key }}]" id="field-{{ $key }}"
                            @if ($isRequired) required @endif>{{ old("field_values.$key") }}</textarea>
                    @endif
                @elseif ($type === 'email')
                    @if ($isCanonical)
                        <input type="email" name="{{ $inputName }}" id="field-{{ $key }}"
                            value="{{ old($inputName) }}" @if ($isRequired) required @endif>
                    @else
                        <input type="email" name="field_values[{{ $key }}]" id="field-{{ $key }}"
                            value="{{ old("field_values.$key") }}" @if ($isRequired) required @endif>
                    @endif
                @elseif ($type === 'url' || $type === 'url_list')
                    @if ($isCanonical && $key === 'social_links')
                        <input type="url" name="{{ $inputName }}" id="field-{{ $key }}"
                            value="{{ old($inputName) }}" placeholder="https://twitter.com/handle"
                            @if ($isRequired) required @endif>
                    @elseif ($isCanonical)
                        <input type="url" name="{{ $inputName }}" id="field-{{ $key }}"
                            value="{{ old($inputName) }}" @if ($isRequired) required @endif>
                    @else
                        <input type="url" name="field_values[{{ $key }}]" id="field-{{ $key }}"
                            value="{{ old("field_values.$key") }}" @if ($isRequired) required @endif>
                    @endif
                @elseif ($type === 'file')
                    {{-- Profile photo URL field per data-model §3.3.
                         Actual upload pipeline is a separate change. --}}
                    <input type="url" name="{{ $inputName ?? "field_values[$key]" }}" id="field-{{ $key }}"
                        value="{{ old($inputName ?? "field_values.$key") }}" placeholder="https://..."
                        @if ($isRequired) required @endif>
                @else
                    @if ($isCanonical)
                        <input type="text" name="{{ $inputName }}" id="field-{{ $key }}"
                            value="{{ old($inputName) }}" @if ($isRequired) required @endif>
                    @else
                        <input type="text" name="field_values[{{ $key }}]" id="field-{{ $key }}"
                            value="{{ old("field_values.$key") }}" @if ($isRequired) required @endif>
                    @endif
                @endif
            @endforeach

            {{-- 'testimonial_text' is rendered by the foreach above when
                 its entry is enabled. The default fields_config includes it. --}}

            @if ($ratingEnabled)
                <label for="rating">⭐ {{ __('messages.public.rating_label') }}</label>
                <div class="rating-picker"
                     role="radiogroup"
                     aria-label="Rating from 1 to 5"
                     data-initial="{{ $oldRating }}">
                    @for ($s = 1; $s <= 5; $s++)
                        <span class="star {{ $s <= $oldRating ? 'is-on' : '' }}"
                              role="radio"
                              tabindex="0"
                              aria-checked="{{ $s === $oldRating ? 'true' : 'false' }}"
                              aria-label="{{ $s }} star{{ $s === 1 ? '' : 's' }}"
                              data-value="{{ $s }}">★</span>
                    @endfor
                    <button type="button" class="clear" data-clear>clear</button>
                </div>
                {{-- Native select kept for accessibility and as the
                     canonical form field that gets submitted. Visually
                     hidden so the picker is the primary control. --}}
                <select name="rating" id="rating" class="sr-only" aria-hidden="true" tabindex="-1">
                    <option value="">No rating</option>
                    @for ($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}" @selected((string) $oldRating === (string) $i)>{{ $i }}</option>
                    @endfor
                </select>
                <script>
                    (function () {
                        var picker = document.querySelector('.rating-picker');
                        if (! picker) { return; }
                        var stars = picker.querySelectorAll('.star');
                        var select = document.getElementById('rating');
                        var clearBtn = picker.querySelector('[data-clear]');
                        if (! select) { return; }

                        function paint(value, hovered) {
                            var threshold = hovered != null ? hovered : value;
                            stars.forEach(function (s) {
                                var v = parseInt(s.getAttribute('data-value'), 10);
                                s.classList.toggle('is-on', v <= threshold);
                            });
                        }

                        stars.forEach(function (star) {
                            star.addEventListener('mouseenter', function () {
                                paint(parseInt(select.value || '0', 10), parseInt(star.getAttribute('data-value'), 10));
                            });
                            star.addEventListener('focus', function () {
                                paint(parseInt(select.value || '0', 10), parseInt(star.getAttribute('data-value'), 10));
                            });
                            star.addEventListener('mouseleave', function () {
                                paint(parseInt(select.value || '0', 10), null);
                            });
                            star.addEventListener('click', function () {
                                var v = parseInt(star.getAttribute('data-value'), 10);
                                select.value = String(v);
                                stars.forEach(function (s) {
                                    var sv = parseInt(s.getAttribute('data-value'), 10);
                                    s.classList.toggle('is-on', sv <= v);
                                    s.setAttribute('aria-checked', sv === v ? 'true' : 'false');
                                });
                            });
                            star.addEventListener('keydown', function (e) {
                                if (e.key === 'Enter' || e.key === ' ') {
                                    e.preventDefault();
                                    star.click();
                                }
                            });
                        });

                        if (clearBtn) {
                            clearBtn.addEventListener('click', function () {
                                select.value = '';
                                stars.forEach(function (s) {
                                    s.classList.remove('is-on');
                                    s.setAttribute('aria-checked', 'false');
                                });
                            });
                        }
                    })();
                </script>
            @endif

            {{-- The StoreTestimonialRequest expects consent_state as a string
                 in {granted, declined, not_asked}. We expose two radio
                 inputs (granted / declined) and a fallback hidden
                 'not_asked' value that the form request will not accept
                 if explicitly chosen. The default is 'granted' on submit. --}}
            <div class="consent">
                <p>
                    <strong>🔒 {{ __('messages.public.sharing_consent') }}:</strong>
                    {{ __('messages.public.sharing_consent_question') }}
                </p>
                <label>
                    <input type="radio" name="consent_state" value="granted"
                        @checked(old('consent_state', 'granted') === 'granted') required>
                    {{ __('messages.public.consent_yes') }}
                </label>
                <label>
                    <input type="radio" name="consent_state" value="declined"
                        @checked(old('consent_state') === 'declined')>
                    {{ __('messages.public.consent_no') }}
                </label>
            </div>

            <div class="submit">
                <button type="submit">🚀 {{ __('messages.public.submit_testimonial') }}</button>
            </div>
        </form>
    @endif
@endsection
