{{--
    Public embed widget iframe content.

    Per docs/data-model.md §3.4 + §8 and PRD §8.

    - Layout is 'masonry' or 'carousel' from $embedConfig->layout.
    - Theme mode (light/dark) and background_color are applied via inline
      styles on the wrapper.
    - animation_enabled adds a CSS class; the actual animation rules
      live in the <style> block below.
    - The view MUST NOT include any link or script that references an
      authenticated route. The `public-routes` spec enforces this.
    - Only testimonials with consent_state='granted' are passed in by
      the controller (single source of truth for the consent invariant).
--}}

@php
    $layoutClass = $embedConfig->layout === 'carousel' ? 'embed-carousel' : 'embed-masonry';
    $themeClass = $embedConfig->theme_mode === 'dark' ? 'embed-dark' : 'embed-light';
    $animClass = $embedConfig->animation_enabled ? 'embed-animated' : '';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Testimonials</title>
    <style>
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; }
        .embed-wrapper { padding: 1.5rem; }
        .embed-light { color: #222; }
        .embed-dark { color: #eee; }
        .embed-masonry { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
        .embed-carousel { display: flex; overflow-x: auto; gap: 1rem; scroll-snap-type: x mandatory; }
        .embed-carousel .embed-card { flex: 0 0 280px; scroll-snap-align: start; }
        .embed-card { padding: 1rem; border-radius: 6px; }
        .embed-light .embed-card { background: #f7f7f7; }
        .embed-dark .embed-card { background: #2a2a2a; }
        .embed-card .embed-name { font-weight: 600; margin-bottom: 0.25rem; }
        .embed-card .embed-company { font-size: 0.875rem; opacity: 0.8; margin-bottom: 0.5rem; }
        .embed-card .embed-text { line-height: 1.5; }
        .embed-card .embed-rating { color: #f5b50a; margin-top: 0.5rem; }
        .embed-empty { text-align: center; opacity: 0.6; padding: 2rem; }
        .embed-animated .embed-card { transition: transform 0.2s ease; }
        .embed-animated .embed-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="embed-wrapper {{ $layoutClass }} {{ $themeClass }} {{ $animClass }}"
         style="background-color: {{ $embedConfig->background_color }};">
        @if ($testimonials->isEmpty())
            <div class="embed-empty">No testimonials yet.</div>
        @else
            @foreach ($testimonials as $t)
                <div class="embed-card">
                    <div class="embed-name">{{ $t->submitter_name }}</div>
                    @if ($t->submitter_company)
                        <div class="embed-company">{{ $t->submitter_company }}</div>
                    @endif
                    <div class="embed-text">{{ $t->testimonial_text }}</div>
                    @if (! is_null($t->rating))
                        <div class="embed-rating">
                            @for ($i = 1; $i <= 5; $i++)
                                {{ $i <= $t->rating ? '★' : '☆' }}
                            @endfor
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</body>
</html>
