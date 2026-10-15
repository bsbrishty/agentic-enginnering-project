{{--
    Dashboard layout — sidebar nav, top bar with user menu, main content area.
    Per docs/data-model.md §4 and PRD §13.

    Modernized with:
    - Inter / Plus Jakarta Sans via Google Fonts
    - CSS variables for shadows, radii, transitions
    - Friendly emojis throughout navigation
    - Light/dark theming via [data-theme] on <html>
    - Locale-aware nav (en / bn)
--}}

@php
    $user = auth()->user();
    $firstSpace = $user ? $user->spaces()->first() : null;
    $currentLocale = app()->getLocale();
@endphp

<!DOCTYPE html>
<html lang="{{ $currentLocale }}" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') — Testimonial SaaS</title>

    {{-- Modern typography (PRD §13). Plus Jakarta Sans for display,
         Inter for body text. Both are designed to look great at UI
         sizes and support tabular numerals for KPI cards. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    {{-- No-flash theme bootstrap. Reads the saved theme from
         localStorage before any CSS is parsed so the page never
         paints in the wrong palette. --}}
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('app_theme');
                if (stored === 'dark' || stored === 'light') {
                    document.documentElement.setAttribute('data-theme', stored);
                }
            } catch (e) { /* localStorage blocked — fall back to light */ }
        })();
    </script>

    <style>
        /* ---------------------------------------------------------------
           Design tokens (CSS variables) — light theme is the default;
           dark theme is opt-in via [data-theme="dark"] on <html>.
           --------------------------------------------------------------- */
        :root,
        [data-theme="light"] {
            --font-display: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --font-body: 'Inter', system-ui, -apple-system, sans-serif;

            --color-bg: #f5f5f7;
            --color-bg-soft: #fafafa;
            --color-card: #ffffff;
            --color-text: #1f2937;
            --color-text-soft: #6b7280;
            --color-text-muted: #9ca3af;
            --color-border: #e5e7eb;
            --color-border-soft: #f3f4f6;

            --color-primary: #1677ff;
            --color-primary-soft: #e6f0ff;
            --color-primary-strong: #0958d9;

            --color-success: #10b981;
            --color-success-soft: #d1fae5;
            --color-warning: #f59e0b;
            --color-warning-soft: #fef3c7;
            --color-danger: #ef4444;
            --color-danger-soft: #fee2e2;

            --gradient-primary: linear-gradient(135deg, #1677ff 0%, #6c5ce7 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            --gradient-pink: linear-gradient(135deg, #ec4899 0%, #f43f5e 100%);
            --gradient-purple: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);

            --sidebar-bg: linear-gradient(180deg, #0f172a 0%, #1a1a1a 100%);
            --sidebar-text: #d1d5db;
            --sidebar-text-strong: #ffffff;
            --topbar-bg: #ffffff;

            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --radius-xl: 18px;

            --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.04);
            --shadow-md: 0 4px 12px rgba(15, 23, 42, 0.06);
            --shadow-lg: 0 12px 32px rgba(15, 23, 42, 0.08);
            --shadow-glow: 0 0 0 4px rgba(22, 119, 255, 0.15);

            --transition-fast: 0.15s ease;
            --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="dark"] {
            --color-bg: #0b1220;
            --color-bg-soft: #0f172a;
            --color-card: #111a2e;
            --color-text: #f8fafc;
            --color-text-soft: #94a3b8;
            --color-text-muted: #64748b;
            --color-border: #1e293b;
            --color-border-soft: #18223a;

            --color-primary: #3b82f6;
            --color-primary-soft: #1e3a8a;
            --color-primary-strong: #60a5fa;

            --color-success: #22c55e;
            --color-success-soft: #064e3b;
            --color-warning: #f59e0b;
            --color-warning-soft: #78350f;
            --color-danger: #ef4444;
            --color-danger-soft: #7f1d1d;

            --gradient-primary: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            --gradient-pink: linear-gradient(135deg, #ec4899 0%, #f43f5e 100%);
            --gradient-purple: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);

            --sidebar-bg: linear-gradient(180deg, #0a0f1e 0%, #0b1220 100%);
            --sidebar-text: #cbd5e1;
            --sidebar-text-strong: #f8fafc;
            --topbar-bg: #0f172a;

            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.4);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 12px 32px rgba(0, 0, 0, 0.5);
            --shadow-glow: 0 0 0 4px rgba(96, 165, 250, 0.25);
        }

        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            font-family: var(--font-body);
            margin: 0; padding: 0;
            background: var(--color-bg);
            color: var(--color-text);
            font-feature-settings: 'cv11', 'ss01';
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            transition: background-color var(--transition), color var(--transition);
        }
        h1, h2, h3, h4, h5 {
            font-family: var(--font-display);
            letter-spacing: -0.01em;
            color: var(--color-text);
        }

        /* Layout shell (unchanged grid; just modernized tokens) */
        .layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            grid-template-rows: 64px 1fr;
            grid-template-areas: "sidebar topbar" "sidebar main";
            min-height: 100vh;
        }
        .sidebar {
            grid-area: sidebar;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            padding: 1.5rem 1rem;
        }
        .sidebar h1 {
            font-size: 1.125rem;
            margin: 0 0 1.75rem;
            color: var(--sidebar-text-strong);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .sidebar h1 .logo {
            display: inline-flex;
            align-items: center; justify-content: center;
            width: 32px; height: 32px;
            background: var(--gradient-primary);
            border-radius: 8px;
            font-size: 1rem;
        }
        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.6rem 0.75rem;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: var(--radius-sm);
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
            font-weight: 500;
            transition: background var(--transition-fast), color var(--transition-fast), transform var(--transition-fast);
        }
        .sidebar nav a .ico { font-size: 1.05rem; line-height: 1; }
        .sidebar nav a:hover { background: rgba(255, 255, 255, 0.08); color: var(--sidebar-text-strong); transform: translateX(2px); }
        .sidebar nav a.active { background: rgba(22, 119, 255, 0.18); color: var(--sidebar-text-strong); font-weight: 600; }

        .topbar {
            grid-area: topbar;
            background: var(--topbar-bg);
            border-bottom: 1px solid var(--color-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.25rem;
            gap: 1rem;
            box-shadow: var(--shadow-sm);
        }
        .topbar .greeting { font-weight: 600; font-size: 0.95rem; color: var(--color-text); }
        .topbar .greeting span { color: var(--color-text-soft); font-weight: 400; margin-left: 0.25rem; }
        .topbar .right { display: flex; align-items: center; gap: 0.5rem; }
        .topbar .user { font-size: 0.9rem; color: var(--color-text-soft); display: inline-flex; align-items: center; gap: 0.5rem; }
        .topbar .user .avatar {
            display: inline-flex; align-items: center; justify-content: center;
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--gradient-primary); color: #fff; font-weight: 600;
        }
        .topbar form { display: inline; }
        .topbar button {
            background: transparent;
            border: 1px solid var(--color-border);
            padding: 0.4rem 0.7rem;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 0.85rem;
            color: var(--color-text);
            transition: all var(--transition-fast);
        }
        .topbar button:hover { background: var(--color-bg-soft); border-color: var(--color-text-muted); }

        /* Theme toggle + language switcher */
        .theme-toggle,
        .lang-switch a {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: transparent;
            border: 1px solid var(--color-border);
            color: var(--color-text);
            padding: 0.4rem 0.65rem;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
            transition: all var(--transition-fast);
        }
        .theme-toggle:hover,
        .lang-switch a:hover {
            background: var(--color-bg-soft);
            border-color: var(--color-text-muted);
        }
        [data-theme="dark"] .theme-toggle:hover,
        [data-theme="dark"] .lang-switch a:hover {
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.18);
        }
        .lang-switch { display: inline-flex; gap: 0.25rem; }
        .lang-switch a.active {
            background: var(--color-primary-soft);
            color: var(--color-primary-strong);
            border-color: var(--color-primary);
            font-weight: 600;
        }
        [data-theme="dark"] .lang-switch a.active {
            color: #bfdbfe;
        }

        .main { grid-area: main; padding: 2rem; max-width: 1400px; }
        .main h1 { margin-top: 0; font-size: 1.625rem; }

        /* Cards & primitives */
        .card {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: box-shadow var(--transition), transform var(--transition), background var(--transition), border-color var(--transition);
        }
        .card:hover { box-shadow: var(--shadow-md); }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 1.25rem 1.4rem;
            position: relative;
            overflow: hidden;
            transition: transform var(--transition), box-shadow var(--transition);
        }
        .stat-card::after {
            content: "";
            position: absolute; right: -16px; top: -16px;
            width: 80px; height: 80px;
            border-radius: 50%;
            opacity: 0.1;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        [data-theme="dark"] .stat-card:hover { box-shadow: var(--shadow-md), 0 0 0 2px rgba(96, 165, 250, 0.15); }
        .stat-card .icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 36px; height: 36px;
            border-radius: var(--radius-md);
            font-size: 1.05rem;
            margin-bottom: 0.5rem;
        }
        .stat-card .label {
            font-size: 0.78rem;
            color: var(--color-text-soft);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 600;
        }
        .stat-card .value {
            font-family: var(--font-display);
            font-size: 1.85rem;
            font-weight: 700;
            margin-top: 0.25rem;
            letter-spacing: -0.02em;
        }
        .stat-card.gradient-blue   { background: var(--gradient-primary); color: #fff; border-color: transparent; }
        .stat-card.gradient-blue::after { background: #fff; }
        .stat-card.gradient-blue .label, .stat-card.gradient-blue .value { color: #fff; }
        .stat-card.gradient-blue .icon  { background: rgba(255, 255, 255, 0.18); color: #fff; }
        .stat-card.gradient-pink   { background: var(--gradient-pink); color: #fff; border-color: transparent; }
        .stat-card.gradient-pink::after { background: #fff; }
        .stat-card.gradient-pink .label, .stat-card.gradient-pink .value { color: #fff; }
        .stat-card.gradient-pink .icon  { background: rgba(255, 255, 255, 0.18); color: #fff; }
        .stat-card.gradient-purple { background: var(--gradient-purple); color: #fff; border-color: transparent; }
        .stat-card.gradient-purple::after { background: #fff; }
        .stat-card.gradient-purple .label, .stat-card.gradient-purple .value { color: #fff; }
        .stat-card.gradient-purple .icon  { background: rgba(255, 255, 255, 0.18); color: #fff; }
        .stat-card.gradient-warning { background: var(--gradient-warning); color: #fff; border-color: transparent; }
        .stat-card.gradient-warning::after { background: #fff; }
        .stat-card.gradient-warning .label, .stat-card.gradient-warning .value { color: #fff; }
        .stat-card.gradient-warning .icon  { background: rgba(255, 255, 255, 0.18); color: #fff; }

        /* Banner (free plan upgrade nudge) */
        .banner {
            background: linear-gradient(135deg, #fff7e6 0%, #ffe7ba 100%);
            border: 1px solid #ffd591;
            color: #92400e;
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: var(--shadow-sm);
        }
        [data-theme="dark"] .banner {
            background: linear-gradient(135deg, #422006 0%, #78350f 100%);
            border-color: #92400e;
            color: #fed7aa;
        }
        .banner a {
            background: var(--color-warning);
            color: #fff;
            padding: 0.55rem 1.1rem;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            transition: all var(--transition-fast);
        }
        .banner a:hover { background: #d97706; transform: translateY(-1px); }

        /* Tables */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.85rem 0.75rem; text-align: left; border-bottom: 1px solid var(--color-border-soft); font-size: 0.9rem; color: var(--color-text); }
        th { background: var(--color-bg-soft); font-weight: 600; color: var(--color-text-soft); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
        tbody tr { transition: background var(--transition-fast); }
        tbody tr:hover { background: var(--color-bg-soft); }
        [data-theme="dark"] tbody tr:hover { background: rgba(96, 165, 250, 0.06); }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.55rem 1.1rem;
            background: var(--color-primary);
            color: #fff;
            text-decoration: none;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all var(--transition-fast);
        }
        .btn:hover { background: var(--color-primary-strong); transform: translateY(-1px); box-shadow: var(--shadow-md); }
        .btn:active { transform: translateY(0); }
        .btn-secondary { background: var(--color-bg-soft); color: var(--color-text); border: 1px solid var(--color-border); }
        .btn-secondary:hover { background: var(--color-card); border-color: var(--color-text-muted); }
        .btn-danger { background: var(--color-danger); }
        .btn-danger:hover { background: #dc2626; }
        .btn-success { background: var(--color-success); }
        .btn-success:hover { background: #059669; }
        .btn-sm { padding: 0.3rem 0.6rem; font-size: 0.8rem; }

        .form-row { margin-bottom: 1rem; }
        .form-row label { display: block; font-weight: 600; margin-bottom: 0.25rem; font-size: 0.9rem; color: var(--color-text); }
        .form-row input[type=text], .form-row input[type=email], .form-row input[type=url],
        .form-row input[type=password], .form-row select, .form-row textarea {
            width: 100%; padding: 0.55rem 0.7rem;
            font-size: 0.95rem;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            background: var(--color-card);
            color: var(--color-text);
            transition: border var(--transition-fast), box-shadow var(--transition-fast);
            font-family: var(--font-body);
        }
        .form-row input:focus, .form-row select:focus, .form-row textarea:focus {
            outline: none; border-color: var(--color-primary); box-shadow: var(--shadow-glow);
        }
        .form-row textarea { min-height: 120px; }
        .form-row .checkbox { display: inline-block; font-weight: normal; }

        .actions { display: flex; gap: 0.4rem; flex-wrap: wrap; }

        /* Status / error messages */
        .status { background: var(--color-success-soft); border: 1px solid #6ee7b7; color: #065f46; padding: 0.85rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1rem; }
        [data-theme="dark"] .status { color: #a7f3d0; }
        .errors { background: var(--color-danger-soft); border: 1px solid #fca5a5; color: #991b1b; padding: 0.85rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1rem; }
        [data-theme="dark"] .errors { color: #fecaca; }
        .errors ul { margin: 0; padding-left: 1.25rem; }

        /* Chart card helpers used by the dashboard view */
        .chart-card .chart-empty { color: var(--color-text-soft); }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <h1><span class="logo">💬</span> {{ __('messages.nav.dashboard') === 'Dashboard' ? 'TestimonialHub' : 'টেস্টিমোনিয়ালহাব' }}</h1>
            <nav>
                <a href="{{ route('dashboard.index') }}" class="{{ request()->routeIs('dashboard.index') ? 'active' : '' }}">
                    <span class="ico">🚀</span> {{ __('messages.nav.dashboard') }}
                </a>
                <a href="{{ route('dashboard.spaces.index') }}" class="{{ request()->routeIs('dashboard.spaces.*') && ! request()->routeIs('dashboard.embeds.*') ? 'active' : '' }}">
                    <span class="ico">🪐</span> {{ __('messages.nav.spaces') }}
                </a>
                <a href="{{ route('dashboard.inbox.index') }}" class="{{ request()->routeIs('dashboard.inbox.*') || request()->routeIs('dashboard.testimonials.*') ? 'active' : '' }}">
                    <span class="ico">💌</span> {{ __('messages.nav.inbox') }}
                </a>
                <a href="{{ $firstSpace ? route('dashboard.embeds.index', $firstSpace) : '#' }}"
                   class="{{ request()->routeIs('dashboard.embeds.*') ? 'active' : '' }}"
                   @if (! $firstSpace) aria-disabled="true" style="opacity: 0.5;" @endif>
                    <span class="ico">❤️</span> {{ __('messages.nav.wall_of_love') }}
                </a>
                <a href="{{ route('dashboard.billing.index') }}" class="{{ request()->routeIs('dashboard.billing.*') ? 'active' : '' }}">
                    <span class="ico">💳</span> {{ __('messages.nav.billing') }}
                </a>
            </nav>
        </aside>

        <header class="topbar">
            <div class="greeting">
                👋 {{ __('messages.common.welcome_back') }}<span>, {{ $user->name ?? 'friend' }}</span>
            </div>
            <div class="right">
                {{-- Language switcher (EN | BN). Plain <a> links so it
                     works without JS and is crawlable. --}}
                <div class="lang-switch" role="group" aria-label="{{ __('messages.common.language') }}">
                    <a href="{{ route('locale.switch', 'en') }}" class="{{ $currentLocale === 'en' ? 'active' : '' }}" rel="nofollow">🇺🇸 EN</a>
                    <a href="{{ route('locale.switch', 'bn') }}" class="{{ $currentLocale === 'bn' ? 'active' : '' }}" rel="nofollow">🇧🇩 BN</a>
                </div>

                {{-- Theme toggle (Sun / Moon). Pure-JS, persists to
                     localStorage and updates <html data-theme>. --}}
                <button type="button" class="theme-toggle" id="themeToggle"
                        title="{{ __('messages.common.toggle_theme') }}"
                        aria-label="{{ __('messages.common.toggle_theme') }}">
                    <span class="theme-icon-light" aria-hidden="true">☀️</span>
                    <span class="theme-icon-dark" aria-hidden="true" style="display:none;">🌙</span>
                    <span class="theme-label" style="margin-left: 0.15rem;">{{ $currentLocale === 'bn' ? 'থিম' : 'Theme' }}</span>
                </button>

                <span class="user">
                    <span class="avatar">{{ strtoupper(mb_substr($user->name ?? 'U', 0, 1)) }}</span>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">{{ __('messages.nav.logout') }}</button>
                </form>
            </div>
        </header>

        <main class="main">
            @if (session('status'))
                <div class="status">🎉 {{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="errors">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <script>
        (function () {
            var STORAGE_KEY = 'app_theme';
            var root = document.documentElement;
            var btn = document.getElementById('themeToggle');
            if (! btn) { return; }

            function paint() {
                var t = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
                var lightIcon = btn.querySelector('.theme-icon-light');
                var darkIcon  = btn.querySelector('.theme-icon-dark');
                if (lightIcon && darkIcon) {
                    lightIcon.style.display = t === 'dark' ? 'none' : '';
                    darkIcon.style.display  = t === 'dark' ? '' : 'none';
                }
            }
            paint();

            btn.addEventListener('click', function () {
                var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', next);
                try { localStorage.setItem(STORAGE_KEY, next); } catch (e) {}
                paint();
            });
        })();
    </script>
</body>
</html>
