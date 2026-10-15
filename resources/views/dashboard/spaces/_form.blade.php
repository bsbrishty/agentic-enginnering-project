{{--
    Shared form partial for create/edit space.
    Per docs/data-model.md §8.1.
--}}

@php
    $isEdit = $space->exists ?? false;
@endphp

<div class="form-row">
    <label for="title">Title <span style="color:#c00">*</span></label>
    <input type="text" name="title" id="title" value="{{ old('title', $space->title ?? '') }}" required>
</div>

<div class="form-row">
    <label for="subtitle">Subtitle</label>
    <input type="text" name="subtitle" id="subtitle" value="{{ old('subtitle', $space->subtitle ?? '') }}">
</div>

<div class="form-row">
    <label for="collection_question">Collection question <span style="color:#c00">*</span></label>
    <textarea name="collection_question" id="collection_question" required>{{ old('collection_question', $space->collection_question ?? '') }}</textarea>
</div>

<div class="form-row">
    <label for="theme">Theme</label>
    <select name="theme" id="theme">
        @foreach (['minimal_light' => 'Minimal Light', 'modern_dark' => 'Modern Dark', 'warm_friendly' => 'Warm and Friendly'] as $value => $label)
            <option value="{{ $value }}" @selected(old('theme', $space->theme ?? 'minimal_light') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="form-row">
    <label>
        <input type="checkbox" name="star_ratings_enabled" value="1"
            @checked(old('star_ratings_enabled', $space->star_ratings_enabled ?? $space->rating_enabled ?? false))>
        Enable star ratings (1–5)
    </label>
</div>

<div class="form-row">
    <label>
        <input type="checkbox" name="is_active" value="1"
            @checked(old('is_active', $space->is_active ?? true))>
        Accepting new submissions
    </label>
</div>

{{--
    The form request doesn't include `fields_config` validation, so we
    serialize the default array as JSON in a hidden field. The controller
    can override this with a structured editor in a later change.
--}}
@php
    $fieldsConfig = old('fields_config') ?: ($space->fields_config ?? $defaultFieldsConfig ?? []);
@endphp
<div class="form-row">
    <label>Form fields</label>
    <p style="font-size: 0.875rem; color: #666;">JSON configuration of which fields are enabled. The default is shown; advanced editing comes later.</p>
    <textarea name="fields_config" id="fields_config" rows="10" style="font-family: monospace; font-size: 0.8rem;">{{ is_array($fieldsConfig) ? json_encode($fieldsConfig, JSON_PRETTY_PRINT) : $fieldsConfig }}</textarea>
</div>

<button type="submit" class="btn">{{ $isEdit ? 'Save changes' : 'Create space' }}</button>
