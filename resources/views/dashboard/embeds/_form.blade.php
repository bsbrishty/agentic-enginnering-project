{{-- Shared embed builder form partial. --}}

@php
    $isEdit = $config !== null;
    $layout = old('layout', $config->layout ?? 'masonry');
    $themeMode = old('theme_mode', $config->theme_mode ?? 'light');
    $bgColor = old('background_color', $config->background_color ?? '#ffffff');
    $anim = old('animation_enabled', $config->animation_enabled ?? true);
@endphp

<div class="form-row">
    <label for="layout">Layout</label>
    <select name="layout" id="layout">
        <option value="masonry" @selected($layout === 'masonry')>Masonry</option>
        <option value="carousel" @selected($layout === 'carousel')>Carousel</option>
    </select>
</div>

<div class="form-row">
    <label for="theme_mode">Theme mode</label>
    <select name="theme_mode" id="theme_mode">
        <option value="light" @selected($themeMode === 'light')>Light</option>
        <option value="dark" @selected($themeMode === 'dark')>Dark</option>
    </select>
</div>

<div class="form-row">
    <label for="background_color">Background color</label>
    <input type="text" name="background_color" id="background_color" value="{{ $bgColor }}"
           pattern="^#[0-9A-Fa-f]{6}$" placeholder="#ffffff">
</div>

<div class="form-row">
    <label>
        <input type="checkbox" name="animation_enabled" value="1" @checked($anim)>
        Enable animations
    </label>
</div>

<button type="submit" class="btn">{{ $isEdit ? 'Save changes' : 'Create embed' }}</button>
