@php
    $currentLocale = app()->getLocale();
@endphp
<!DOCTYPE html>
<html lang="{{ $currentLocale }}" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Testimonial SaaS')</title>
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
            --guest-font-display: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --guest-font-body: 'Inter', system-ui, -apple-system, sans-serif;

            --guest-text: #1f2937;
            --guest-text-soft: #6b7280;
            --guest-text-muted: #94a3b8;
            --guest-border: #e2e8f0;
            --guest-border-strong: #cbd5e1;
            --guest-card: #ffffff;
            --guest-primary: #1677ff;
            --guest-primary-strong: #0958d9;
            --guest-success: #10b981;
            --guest-danger: #ef4444;

            --guest-bg:
                radial-gradient(circle at 15% 10%, rgba(22, 119, 255, 0.12) 0%, transparent 45%),
                radial-gradient(circle at 85% 90%, rgba(236, 72, 153, 0.10) 0%, transparent 50%),
                #f8fafc;
            --guest-logo-gradient: linear-gradient(135deg, #1677ff 0%, #6366f1 100%);
            --guest-btn-gradient: linear-gradient(135deg, #1677ff 0%, #6366f1 100%);
            --guest-errors-bg: #fef2f2;
            --guest-errors-border: #fecaca;
            --guest-errors-text: #991b1b;
            --guest-status-bg: #ecfdf5;
            --guest-status-border: #a7f3d0;
            --guest-status-text: #065f46;
        }

        [data-theme="dark"] {
            --guest-text: #f8fafc;
            --guest-text-soft: #94a3b8;
            --guest-text-muted: #64748b;
            --guest-border: #1e293b;
            --guest-border-strong: #334155;
            --guest-card: #0f172a;
            --guest-primary: #60a5fa;
            --guest-primary-strong: #93c5fd;

            --guest-bg:
                radial-gradient(circle at 15% 10%, rgba(96, 165, 250, 0.18) 0%, transparent 45%),
                radial-gradient(circle at 85% 90%, rgba(236, 72, 153, 0.14) 0%, transparent 50%),
                #050a16;
            --guest-logo-gradient: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
            --guest-btn-gradient: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            --guest-errors-bg: rgba(127, 29, 29, 0.25);
            --guest-errors-border: #7f1d1d;
            --guest-errors-text: #fecaca;
            --guest-status-bg: rgba(6, 95, 70, 0.25);
            --guest-status-border: #047857;
            --guest-status-text: #a7f3d0;
        }

        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            font-family: var(--guest-font-body);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: var(--guest-bg);
            color: var(--guest-text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            transition: background 0.25s ease, color 0.25s ease;
        }

        h1, h2, h3 {
            font-family: var(--guest-font-display);
            letter-spacing: -0.01em;
            color: var(--guest-text);
        }

        /* Floating glassmorphism card */
        .guest-wrap {
            position: relative;
            width: 100%;
            max-width: 440px;
            padding: 2.25rem 2rem 2rem;
            background: var(--guest-card);
            border: 1px solid var(--guest-border);
            border-radius: 16px;
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.7) inset,
                0 20px 40px -15px rgba(0, 0, 0, 0.08),
                0 4px 12px rgba(15, 23, 42, 0.04);
            backdrop-filter: blur(12px) saturate(140%);
            -webkit-backdrop-filter: blur(12px) saturate(140%);
            transition: background 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
        }
        [data-theme="dark"] .guest-wrap {
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.06) inset,
                0 20px 40px -15px rgba(0, 0, 0, 0.6),
                0 4px 12px rgba(0, 0, 0, 0.4);
        }

        /* Decorative top gradient ribbon */
        .guest-wrap::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--guest-logo-gradient);
            border-radius: 16px 16px 0 0;
        }

        .guest-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-family: var(--guest-font-display);
            font-weight: 800;
            font-size: 1.1rem;
            letter-spacing: -0.01em;
        }
        .guest-brand .brand-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: var(--guest-logo-gradient);
            color: #fff;
            border-radius: 8px;
            font-size: 1rem;
            box-shadow: 0 4px 10px rgba(22, 119, 255, 0.3);
        }
        .guest-brand .brand-text {
            background: var(--guest-logo-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .guest-header h1 {
            font-size: 1.6rem;
            margin: 0 0 0.35rem;
            font-weight: 800;
        }
        .guest-header .subtitle {
            margin: 0 0 1.5rem;
            color: var(--guest-text-soft);
            font-size: 0.95rem;
        }

        /* Form rows */
        .guest-form .field { margin-top: 1rem; }
        .guest-form .field label {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--guest-text);
            margin-bottom: 0.35rem;
        }
        .guest-form .field label .ico { font-size: 0.95rem; line-height: 1; }
        .guest-form .field input[type=text],
        .guest-form .field input[type=email],
        .guest-form .field input[type=password],
        .guest-form .field input[type=url] {
            width: 100%;
            padding: 0.7rem 0.85rem;
            font-size: 1rem;
            font-family: var(--guest-font-body);
            color: var(--guest-text);
            background: var(--guest-card);
            border: 1px solid var(--guest-border-strong);
            border-radius: 10px;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .guest-form .field input::placeholder { color: var(--guest-text-muted); }
        .guest-form .field input:focus {
            outline: none;
            border-color: var(--guest-primary);
            box-shadow: 0 0 0 4px rgba(22, 119, 255, 0.15);
        }
        [data-theme="dark"] .guest-form .field input:focus {
            box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.25);
        }

        /* Inline checkbox */
        .guest-form .remember {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: var(--guest-text);
            cursor: pointer;
            user-select: none;
        }
        .guest-form .remember input[type=checkbox] {
            accent-color: var(--guest-primary);
            width: 16px;
            height: 16px;
        }

        /* Gradient submit button */
        .guest-form button[type=submit] {
            margin-top: 1.5rem;
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 600;
            font-family: var(--guest-font-body);
            background: var(--guest-btn-gradient);
            color: #fff;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(22, 119, 255, 0.28);
            transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
        }
        .guest-form button[type=submit]:hover {
            transform: scale(1.015) translateY(-1px);
            box-shadow: 0 12px 24px rgba(22, 119, 255, 0.36);
            filter: brightness(1.05);
        }
        .guest-form button[type=submit]:active {
            transform: scale(0.99);
        }

        /* Footer links */
        .guest-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
            color: var(--guest-text-soft);
        }
        .guest-footer a {
            color: var(--guest-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.15s ease, text-decoration-color 0.15s ease;
            text-underline-offset: 3px;
        }
        .guest-footer a:hover {
            text-decoration: underline;
            color: var(--guest-primary-strong);
        }
        .guest-footer p { margin: 0.35rem 0; }

        /* Status / error messages */
        .guest-wrap .status {
            background: var(--guest-status-bg);
            border: 1px solid var(--guest-status-border);
            color: var(--guest-status-text);
            padding: 0.85rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
        }
        .guest-wrap .errors {
            background: var(--guest-errors-bg);
            border: 1px solid var(--guest-errors-border);
            color: var(--guest-errors-text);
            padding: 0.85rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
        }
        .guest-wrap .errors ul { margin: 0.4rem 0 0; padding-left: 1.25rem; }

        /* Small floating theme + language utilities */
        .guest-utils {
            position: fixed;
            top: 0.75rem;
            right: 0.75rem;
            display: flex;
            gap: 0.4rem;
            z-index: 10;
        }
        .guest-utils .util-btn,
        .guest-utils .util-link {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background: var(--guest-card);
            color: var(--guest-text);
            border: 1px solid var(--guest-border);
            padding: 0.35rem 0.6rem;
            border-radius: 8px;
            font-size: 0.8rem;
            text-decoration: none;
            cursor: pointer;
        }
        .guest-utils .util-link.active {
            background: var(--guest-primary);
            color: #fff;
            border-color: var(--guest-primary);
        }

        @media (max-width: 480px) {
            .guest-wrap { padding: 1.75rem 1.25rem; border-radius: 14px; }
            body { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="guest-utils">
        <a class="util-link {{ $currentLocale === 'en' ? 'active' : '' }}" href="{{ route('locale.switch', 'en') }}" rel="nofollow">🇺🇸 EN</a>
        <a class="util-link {{ $currentLocale === 'bn' ? 'active' : '' }}" href="{{ route('locale.switch', 'bn') }}" rel="nofollow">🇧🇩 BN</a>
        <button type="button" class="util-btn" id="themeToggleGuest" title="Toggle theme" aria-label="Toggle theme">
            <span class="theme-icon-light" aria-hidden="true">☀️</span>
            <span class="theme-icon-dark" aria-hidden="true" style="display:none;">🌙</span>
        </button>
    </div>

    <div class="guest-wrap">
        @yield('content')
    </div>

    <script>
        (function () {
            var btn = document.getElementById('themeToggleGuest');
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