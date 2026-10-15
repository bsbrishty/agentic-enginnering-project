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

    Modernization (PRD §13):
    - Glassmorphism cards with backdrop-filter and gradient borders.
    - Glowing golden stars with soft drop-shadow.
    - Gradient ring around avatar initials.
    - Decorative opening quote glyph and a soft divider.
    - Hover: subtle scale + lift + intensified glow.
    - Animation: staggered fade-in/translate-up when animation_enabled.
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
    <title>Wall of Love</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --wol-font-display: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --wol-font-body: 'Inter', system-ui, -apple-system, sans-serif;
            --wol-gold: #fbbf24;
            --wol-gold-soft: #fde68a;
            --wol-pink: #ec4899;
            --wol-purple: #8b5cf6;
            --wol-blue: #1677ff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: var(--wol-font-body);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .embed-wrapper { padding: 1.75rem 1.5rem; }

        /* ----- Layouts ----- */
        .embed-masonry {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1.25rem;
        }
        .embed-carousel {
            display: flex;
            overflow-x: auto;
            gap: 1.25rem;
            scroll-snap-type: x mandatory;
            padding-bottom: 0.5rem;
            scrollbar-width: thin;
        }
        .embed-carousel .embed-card { flex: 0 0 300px; scroll-snap-align: start; }

        /* ----- Theme tokens ----- */
        .embed-light {
            color: #1f2937;
            background:
                radial-gradient(circle at 20% 10%, rgba(22, 119, 255, 0.06) 0%, transparent 40%),
                radial-gradient(circle at 80% 30%, rgba(236, 72, 153, 0.05) 0%, transparent 45%);
        }
        .embed-dark {
            color: #e5e7eb;
            background:
                radial-gradient(circle at 20% 10%, rgba(139, 92, 246, 0.18) 0%, transparent 40%),
                radial-gradient(circle at 80% 30%, rgba(22, 119, 255, 0.12) 0%, transparent 45%),
                #0b1020;
        }

        /* ----- Card (glassmorphism + gradient border) ----- */
        .embed-card {
            position: relative;
            padding: 1.5rem 1.4rem 1.25rem;
            border-radius: 16px;
            overflow: hidden;
            isolation: isolate;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                        box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                        border-color 0.3s ease;
        }
        /* Gradient border via padding-box / border-image trick */
        .embed-card::before {
            content: "";
            position: absolute;
            inset: 0;
            padding: 1px;
            border-radius: inherit;
            background: linear-gradient(135deg, rgba(22, 119, 255, 0.45), rgba(236, 72, 153, 0.45), rgba(139, 92, 246, 0.45));
            -webkit-mask:
                linear-gradient(#000 0 0) content-box,
                linear-gradient(#000 0 0);
            -webkit-mask-composite: xor;
                    mask-composite: exclude;
            opacity: 0.7;
            transition: opacity 0.3s ease;
            z-index: -1;
        }
        .embed-light .embed-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(14px) saturate(140%);
            -webkit-backdrop-filter: blur(14px) saturate(140%);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.7) inset,
                0 8px 24px rgba(15, 23, 42, 0.06);
        }
        .embed-dark .embed-card {
            background: rgba(20, 24, 48, 0.55);
            backdrop-filter: blur(14px) saturate(140%);
            -webkit-backdrop-filter: blur(14px) saturate(140%);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.06) inset,
                0 12px 32px rgba(0, 0, 0, 0.35);
        }

        /* Hover: lift + intensified glow */
        .embed-animated .embed-card {
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1),
                        box-shadow 0.35s ease;
        }
        .embed-card:hover {
            transform: translateY(-4px) scale(1.015);
        }
        .embed-light .embed-card:hover {
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.7) inset,
                0 18px 40px rgba(22, 119, 255, 0.18),
                0 0 0 4px rgba(22, 119, 255, 0.08);
        }
        .embed-dark .embed-card:hover {
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.08) inset,
                0 18px 40px rgba(0, 0, 0, 0.5),
                0 0 0 4px rgba(139, 92, 246, 0.18);
        }
        .embed-card:hover::before { opacity: 1; }

        /* Decorative opening quote */
        .embed-card .embed-quote {
            position: absolute;
            top: -6px;
            right: 14px;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 4.5rem;
            line-height: 1;
            color: rgba(22, 119, 255, 0.15);
            pointer-events: none;
            z-index: -1;
            user-select: none;
        }
        .embed-dark .embed-card .embed-quote { color: rgba(139, 92, 246, 0.22); }

        /* Header row: avatar + name + company */
        .embed-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.85rem;
        }
        .embed-avatar {
            flex: 0 0 44px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: var(--wol-font-display);
            font-weight: 700;
            font-size: 1rem;
            color: #fff;
            background: linear-gradient(135deg, #1677ff 0%, #6c5ce7 100%);
            box-shadow:
                0 0 0 2px #fff inset,
                0 4px 10px rgba(22, 119, 255, 0.3);
            position: relative;
        }
        .embed-dark .embed-avatar {
            box-shadow:
                0 0 0 2px rgba(20, 24, 48, 0.7) inset,
                0 0 0 1px rgba(255, 255, 255, 0.08),
                0 4px 14px rgba(139, 92, 246, 0.35);
        }
        /* Outer gradient ring */
        .embed-avatar::after {
            content: "";
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            padding: 2px;
            background: conic-gradient(from 180deg, #1677ff, #ec4899, #8b5cf6, #1677ff);
            -webkit-mask:
                linear-gradient(#000 0 0) content-box,
                linear-gradient(#000 0 0);
            -webkit-mask-composite: xor;
                    mask-composite: exclude;
            opacity: 0.55;
            z-index: -1;
        }
        .embed-identity { display: flex; flex-direction: column; min-width: 0; }
        .embed-name {
            font-family: var(--wol-font-display);
            font-weight: 700;
            font-size: 0.95rem;
            line-height: 1.2;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .embed-company {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-top: 1px;
        }

        /* Verified badge (next to name) */
        .embed-verified {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #06b6d4);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.35);
        }

        /* Body text */
        .embed-text {
            font-size: 0.92rem;
            line-height: 1.55;
            margin: 0 0 0.85rem;
            word-wrap: break-word;
        }
        .embed-dark .embed-text { color: #e5e7eb; }

        /* Divider before rating */
        .embed-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(22, 119, 255, 0.18), transparent);
            margin: 0.5rem 0 0.6rem;
        }
        .embed-dark .embed-divider {
            background: linear-gradient(90deg, transparent, rgba(139, 92, 246, 0.35), transparent);
        }

        /* Glowing golden stars */
        .embed-rating {
            display: inline-flex;
            align-items: center;
            gap: 0.15rem;
            color: var(--wol-gold);
            font-size: 1rem;
            line-height: 1;
            text-shadow:
                0 0 6px rgba(251, 191, 36, 0.6),
                0 0 12px rgba(251, 191, 36, 0.25);
            letter-spacing: 0.05em;
        }
        .embed-rating .embed-star-on { color: var(--wol-gold); }
        .embed-rating .embed-star-off {
            color: rgba(156, 163, 175, 0.45);
            text-shadow: none;
        }

        /* Empty state */
        .embed-empty {
            text-align: center;
            opacity: 0.7;
            padding: 2.5rem 1rem;
            font-size: 0.95rem;
        }

        /* Staggered fade-in when animation_enabled. We use the inline
           --idx CSS variable from the template to delay each card. */
        .embed-animated .embed-card {
            opacity: 0;
            transform: translateY(8px);
            animation: embed-rise 0.55s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            animation-delay: calc(var(--idx, 0) * 70ms);
        }
        @keyframes embed-rise {
            to { opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .embed-animated .embed-card {
                animation: none;
                opacity: 1;
                transform: none;
            }
        }
    </style>
</head>
<body>
    <div class="embed-wrapper {{ $layoutClass }} {{ $themeClass }} {{ $animClass }}"
         style="background-color: {{ $embedConfig->background_color }};">
        @if ($testimonials->isEmpty())
            <div class="embed-empty">💭 No testimonials yet — be the first to share!</div>
        @else
            @foreach ($testimonials as $i => $t)
                <div class="embed-card" style="--idx: {{ $i }};">
                    <span class="embed-quote" aria-hidden="true">&ldquo;</span>
                    <div class="embed-header">
                        <span class="embed-avatar" aria-hidden="true">
                            {{ strtoupper(mb_substr($t->submitter_name ?? 'A', 0, 1)) }}
                        </span>
                        <div class="embed-identity">
                            <div class="embed-name">
                                <span>{{ $t->submitter_name }}</span>
                                <span class="embed-verified" title="Verified" aria-label="Verified">✓</span>
                            </div>
                            @if ($t->submitter_company)
                                <div class="embed-company">{{ $t->submitter_company }}</div>
                            @endif
                        </div>
                    </div>
                    <p class="embed-text">{{ $t->testimonial_text }}</p>
                    <div class="embed-divider"></div>
                    @if (! is_null($t->rating))
                        <div class="embed-rating" aria-label="Rating: {{ $t->rating }} out of 5">
                            @for ($s = 1; $s <= 5; $s++)
                                <span class="{{ $s <= $t->rating ? 'embed-star-on' : 'embed-star-off' }}">★</span>
                            @endfor
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</body>
</html>
