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
      with a friendly confirmation (PRD §6: "You're officially awesome! 🎉").
--}}

@extends('layouts.public')

@section('title', $space->title)

@section('content')
    @if ($submitted)
        <div class="confirmation">
            <h1>You're officially awesome! 🎉</h1>
            <p>Your words made our day. Thank you for sharing.</p>
        </div>
    @else
        <h1>{{ $space->title }}</h1>
        @if ($space->subtitle)
            <h2>{{ $space->subtitle }}</h2>
        @endif
        <p>{{ $space->collection_question }}</p>

        @if ($errors->any())
            <div class="errors">
                <strong>Please fix the following:</strong>
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

            @foreach ($space->fields_config as $field)
                @if (! ($field['enabled'] ?? false))
                    @continue
                @endif
                @php
                    $key = $field['key'];
                    $isRequired = $field['required'] ?? false;
                    $type = $field['type'] ?? 'text';
                @endphp
                <label for="field-{{ $key }}">
                    {{ ucwords(str_replace('_', ' ', $key)) }}
                    @if ($isRequired)<span class="required">*</span>@endif
                </label>
                @if ($type === 'textarea')
                    <textarea name="{{ $key }}" id="field-{{ $key }}"
                        @if ($isRequired) required @endif>{{ old($key) }}</textarea>
                @elseif ($type === 'email')
                    <input type="email" name="{{ $key }}" id="field-{{ $key }}"
                        value="{{ old($key) }}" @if ($isRequired) required @endif>
                @elseif ($type === 'url' || $type === 'url_list')
                    <input type="url" name="{{ $key }}" id="field-{{ $key }}"
                        value="{{ old($key) }}" @if ($isRequired) required @endif>
                @elseif ($type === 'file')
                    {{-- Profile photo URL field per data-model §3.3.
                         Actual upload pipeline is a separate change. --}}
                    <input type="url" name="{{ $key }}" id="field-{{ $key }}"
                        value="{{ old($key) }}" placeholder="https://..."
                        @if ($isRequired) required @endif>
                @else
                    <input type="text" name="{{ $key }}" id="field-{{ $key }}"
                        value="{{ old($key) }}" @if ($isRequired) required @endif>
                @endif
            @endforeach

            {{-- 'testimonial_text' is rendered by the foreach above when
                 its entry is enabled. The default fields_config includes it. --}}

            @if ($space->rating_enabled)
                <label for="rating">Rating (1–5)</label>
                <select name="rating" id="rating">
                    <option value="">No rating</option>
                    @for ($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}" @selected(old('rating') == $i)>{{ $i }}</option>
                    @endfor
                </select>
            @endif

            {{-- The StoreTestimonialRequest expects consent_state as a string
                 in {granted, declined, not_asked}. We expose two radio
                 inputs (granted / declined) and a fallback hidden
                 'not_asked' value that the form request will not accept
                 if explicitly chosen. The default is 'granted' on submit. --}}
            <div class="consent">
                <p>
                    <strong>Sharing consent:</strong>
                    May we share your testimonial publicly and on social networks?
                </p>
                <label>
                    <input type="radio" name="consent_state" value="granted"
                        @checked(old('consent_state', 'granted') === 'granted') required>
                    Yes, you may share it
                </label>
                <label>
                    <input type="radio" name="consent_state" value="declined"
                        @checked(old('consent_state') === 'declined')>
                    No, do not share publicly
                </label>
            </div>

            <div class="submit">
                <button type="submit">Submit testimonial</button>
            </div>
        </form>
    @endif
@endsection
