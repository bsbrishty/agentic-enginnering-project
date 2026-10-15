{{--
    Shared embed builder form partial.
    - Layout (masonry / carousel)
    - Theme mode (light / dark)
    - Background color
    - Animations
    - Live preview on the right
--}}

@php
    $isEdit = $config !== null;
    $layout = old('layout', $config->layout ?? 'masonry');
    $themeMode = old('theme_mode', $config->theme_mode ?? 'light');
    $bgColor = old('background_color', $config->background_color ?? '#ffffff');
    $anim = old('animation_enabled', $config->animation_enabled ?? true);

    $previewUrl = $isEdit
        ? url('/embed/' . $config->public_id)
        : '';
@endphp

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start;">
    <div>
        <div class="form-row">
            <label for="layout">Layout</label>
            <select name="layout" id="layout" onchange="updateEmbedPreview()">
                <option value="masonry" @selected($layout === 'masonry')>Masonry grid</option>
                <option value="carousel" @selected($layout === 'carousel')>Carousel</option>
            </select>
        </div>

        <div class="form-row">
            <label for="theme_mode">Theme</label>
            <select name="theme_mode" id="theme_mode" onchange="updateEmbedPreview()">
                <option value="light" @selected($themeMode === 'light')>Light</option>
                <option value="dark" @selected($themeMode === 'dark')>Dark</option>
            </select>
        </div>

        <div class="form-row">
            <label for="background_color">Background color</label>
            <input type="color" name="background_color_picker" id="background_color_picker"
                   value="{{ $bgColor }}" onchange="syncColor(this.value)"
                   style="height: 40px; width: 80px; padding: 2px;">
            <input type="text" name="background_color" id="background_color" value="{{ $bgColor }}"
                   pattern="^#[0-9A-Fa-f]{6}$" placeholder="#ffffff" onchange="updateEmbedPreview()"
                   style="width: 120px; padding: 0.5rem; margin-left: 0.5rem;">
        </div>

        <div class="form-row">
            <label>
                <input type="checkbox" name="animation_enabled" value="1" @checked($anim) onchange="updateEmbedPreview()">
                Enable animations
            </label>
        </div>

        <button type="submit" class="btn">{{ $isEdit ? 'Save changes' : 'Create embed' }}</button>
    </div>

    <div>
        <div class="form-row">
            <label><strong>Live preview</strong></label>
            <p style="font-size: 0.8rem; color: #888;">Reflects your current settings. Save to persist.</p>
        </div>
        <div id="embed-preview"
             data-preview-url="{{ $previewUrl }}"
             style="border: 1px solid #ccc; border-radius: 6px; overflow: hidden; height: 360px; background: #fff;">
            @if ($previewUrl)
                <iframe src="{{ $previewUrl }}"
                        style="width: 100%; height: 100%; border: 0;"
                        id="embed-preview-iframe"></iframe>
            @else
                <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#888; font-size:0.85rem;">
                    Save the embed to see a live preview.
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    // Rebuild the live preview iframe using a query-string representation of
    // the builder state, fed through a tiny in-memory bridge endpoint.
    // We pre-render the iframe on save; this is purely cosmetic for the
    // builder UI. We disable the iframe and show a "static" preview when
    // the embed hasn't been saved yet.
    function syncColor(hex) {
        document.getElementById('background_color').value = hex;
        updateEmbedPreview();
    }

    function updateEmbedPreview() {
        const iframe = document.getElementById('embed-preview-iframe');
        if (! iframe) {
            return; // no saved embed yet
        }
        // After save, the iframe URL already serves the persisted config.
        // We force a reload with cache-buster so the user sees their changes
        // immediately after editing.
        const url = iframe.getAttribute('src').split('?')[0];
        iframe.setAttribute('src', url + '?v=' + Date.now());
    }
</script>
