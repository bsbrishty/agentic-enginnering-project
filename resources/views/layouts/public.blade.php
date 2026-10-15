@php
    $currentLocale = app()->getLocale();
@endphp
<!DOCTYPE html>
<html lang="{{ $currentLocale }}" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Testimonial')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    {{-- No-flash theme bootstrap. --}}
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('app_theme');
                if (stored === 'dark' || stored === 'light') {
                    document.documentElement.setAttribute('data-theme', stored);
                }
            } catch (e) { /* localStorage blocked */ }
        })();
    </script>

    <style>
        :root,
        [data-theme="light"] {
            --pub-font-display: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --pub-font-body: 'Inter', system-ui, -apple-system, sans-serif;
            --pub-primary: #1677ff;
            --pub-primary-strong: #0958d9;
            --pub-text: #1f2937;
            --pub-text-soft: #6b7280;
            --pub-border: #e5e7eb;
            --pub-card: #ffffff;
            --pub-success: #10b981;
            --pub-danger: #ef4444;
            --pub-gold: #fbbf24;
            --pub-bg:
                radial-gradient(circle at 15% 0%, rgba(22, 119, 255, 0.10) 0%, transparent 45%),
                radial-gradient(circle at 85% 20%, rgba(236, 72, 153, 0.10) 0%, transparent 50%),
                radial-gradient(circle at 50% 100%, rgba(139, 92, 246, 0.10) 0%, transparent 50%),
                #f7f8fb;
            --pub-card-bg: #ffffff;
            --pub-consent-bg: linear-gradient(135deg, #f0f7ff 0%, #faf5ff 100%);
            --pub-consent-border: #dbeafe;
            --pub-confirm-bg: linear-gradient(135deg, #ecfdf5 0%, #f0f9ff 100%);
            --pub-confirm-border: #a7f3d0;
            --pub-confirm-text: #065f46;
            --pub-inactive-bg: #fff7ed;
            --pub-inactive-border: #fed7aa;
            --pub-inactive-text: #9a3412;
        }

        [data-theme="dark"] {
            --pub-primary: #60a5fa;
            --pub-primary-strong: #93c5fd;
            --pub-text: #f8fafc;
            --pub-text-soft: #94a3b8;
            --pub-border: #1e293b;
            --pub-card: #0f172a;
            --pub-success: #22c55e;
            --pub-danger: #f87171;
            --pub-gold: #fbbf24;
            --pub-bg:
                radial-gradient(circle at 15% 0%, rgba(96, 165, 250, 0.15) 0%, transparent 45%),
                radial-gradient(circle at 85% 20%, rgba(236, 72, 153, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 50% 100%, rgba(139, 92, 246, 0.18) 0%, transparent 50%),
                #050a16;
            --pub-card-bg: #0f172a;
            --pub-consent-bg: linear-gradient(135deg, #0b1c3a 0%, #1a1140 100%);
            --pub-consent-border: #1e3a8a;
            --pub-confirm-bg: linear-gradient(135deg, #064e3b 0%, #0c4a6e 100%);
            --pub-confirm-border: #047857;
            --pub-confirm-text: #a7f3d0;
            --pub-inactive-bg: #1c0f06;
            --pub-inactive-border: #7c2d12;
            --pub-inactive-text: #fed7aa;
        }

        * { box-sizing: border-box; }
        body {
            font-family: var(--pub-font-body);
            margin: 0;
            padding: 0;
            color: var(--pub-text);
            background: var(--pub-bg);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            transition: background var(--transition, 0.25s ease), color 0.25s ease;
        }
        .public-container {
            max-width: 640px;
            margin: 0 auto;
            padding: 2.5rem 1.25rem 3rem;
            position: relative;
        }
        .public-container h1 {
            font-family: var(--pub-font-display);
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin: 0 0 0.35rem;
        }
        .public-container h2 {
            font-size: 1.05rem;
            font-weight: 500;
            color: var(--pub-text-soft);
            margin: 0 0 1.25rem;
        }
        .public-container label {
            display: block;
            margin-top: 1rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--pub-text);
        }
        .public-container input[type=text],
        .public-container input[type=email],
        .public-container input[type=url],
        .public-container input[type=number],
        .public-container textarea,
        .public-container select {
            width: 100%;
            padding: 0.7rem 0.85rem;
            font-size: 1rem;
            font-family: var(--pub-font-body);
            box-sizing: border-box;
            border: 1px solid var(--pub-border);
            border-radius: 10px;
            background: var(--pub-card-bg);
            color: var(--pub-text);
            margin-top: 0.35rem;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.25s ease;
        }
        .public-container input:focus,
        .public-container textarea:focus,
        .public-container select:focus {
            outline: none;
            border-color: var(--pub-primary);
            box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.18);
        }
        .public-container textarea { min-height: 130px; }
        .public-container .required { color: var(--pub-danger); margin-left: 2px; }

        .public-container .consent {
            margin-top: 1.75rem;
            padding: 1.15rem 1.25rem;
            background: var(--pub-consent-bg);
            border: 1px solid var(--pub-consent-border);
            border-radius: 12px;
        }
        .public-container .consent p { margin: 0 0 0.75rem; }
        .public-container .consent label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            margin-top: 0.5rem;
            padding: 0.45rem 0.6rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .public-container .consent label:hover { background: rgba(96, 165, 250, 0.08); }
        .public-container .consent input[type=radio] {
            accent-color: var(--pub-primary);
            width: 18px;
            height: 18px;
        }

        .public-container .submit {
            margin-top: 1.75rem;
            display: flex;
            justify-content: flex-end;
        }
        .public-container button[type=submit] {
            padding: 0.85rem 1.6rem;
            font-size: 1rem;
            font-weight: 600;
            font-family: var(--pub-font-body);
            cursor: pointer;
            background: linear-gradient(135deg, #1677ff 0%, #6c5ce7 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            box-shadow: 0 6px 18px rgba(22, 119, 255, 0.28);
            transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
        }
        [data-theme="dark"] .public-container button[type=submit] {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            box-shadow: 0 6px 18px rgba(96, 165, 250, 0.28);
        }
        .public-container button[type=submit]:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(22, 119, 255, 0.36);
            filter: brightness(1.05);
        }
        .public-container button[type=submit]:active { transform: translateY(0); }

        .public-container .errors {
            background: #fef2f2;
            border: 1px solid #fecaca;
            padding: 1rem 1.15rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            color: #991b1b;
        }
        [data-theme="dark"] .public-container .errors {
            background: rgba(127, 29, 29, 0.3);
            border-color: #7f1d1d;
            color: #fecaca;
        }
        .public-container .errors ul { margin: 0.5rem 0 0; padding-left: 1.25rem; }

        .public-container .confirmation {
            text-align: center;
            padding: 3rem 1rem 2rem;
            background: var(--pub-confirm-bg);
            border: 1px solid var(--pub-confirm-border);
            border-radius: 16px;
            position: relative;
            overflow: hidden;
        }
        .public-container .confirmation h1 {
            font-size: 2rem;
            background: linear-gradient(135deg, #10b981 0%, #1677ff 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin: 0 0 0.5rem;
        }
        .public-container .confirmation p { color: var(--pub-confirm-text); margin: 0; font-size: 1.05rem; }

        /* Top-right utilities (theme + language) on the public layout */
        .public-utils {
            position: fixed;
            top: 0.75rem;
            right: 0.75rem;
            display: flex;
            gap: 0.4rem;
            z-index: 10;
        }
        .public-utils .util-btn,
        .public-utils .util-link {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background: var(--pub-card-bg);
            color: var(--pub-text);
            border: 1px solid var(--pub-border);
            padding: 0.35rem 0.6rem;
            border-radius: 8px;
            font-size: 0.8rem;
            text-decoration: none;
            cursor: pointer;
        }
        .public-utils .util-link.active {
            background: var(--pub-primary);
            color: #fff;
            border-color: var(--pub-primary);
        }
    </style>
</head>
<body>
    <div class="public-utils">
        <a class="util-link {{ $currentLocale === 'en' ? 'active' : '' }}" href="{{ route('locale.switch', 'en') }}" rel="nofollow">🇺🇸 EN</a>
        <a class="util-link {{ $currentLocale === 'bn' ? 'active' : '' }}" href="{{ route('locale.switch', 'bn') }}" rel="nofollow">🇧🇩 BN</a>
        <button type="button" class="util-btn" id="themeTogglePublic" title="Toggle theme" aria-label="Toggle theme">
            <span class="theme-icon-light" aria-hidden="true">☀️</span>
            <span class="theme-icon-dark" aria-hidden="true" style="display:none;">🌙</span>
        </button>
    </div>

    <div class="public-container">
        @yield('content')
    </div>

    <script>
        (function () {
            var btn = document.getElementById('themeTogglePublic');
            if (! btn) { return; }
            function paint() {
                var dark = document.documentElement.getAttribute('data-theme') === 'dark';
                var lightIcon = btn.querySelector('.theme-icon-light');
                var darkIcon  = btn.querySelector('.theme-icon-dark');
                if (lightIcon && darkIcon) {
                    lightIcon.style.display = dark ? 'none' : '';
                    darkIcon.style.display  = dark ? '' : 'none';
                }
            }
            paint();
            btn.addEventListener('click', function () {
                var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                try { localStorage.setItem('app_theme', next); } catch (e) {}
                paint();
            });
        })();
    </script>
</body>
</html>
