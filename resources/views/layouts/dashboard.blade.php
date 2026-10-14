{{--
    Dashboard layout — sidebar nav, top bar with user menu, main content area.
    Per docs/data-model.md §4 and PRD §13.
--}}

@php
    $user = auth()->user();
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') — Testimonial SaaS</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; margin: 0; padding: 0; background: #f5f5f7; color: #222; }
        .layout { display: grid; grid-template-columns: 220px 1fr; grid-template-rows: 56px 1fr; grid-template-areas: "sidebar topbar" "sidebar main"; min-height: 100vh; }
        .sidebar { grid-area: sidebar; background: #1a1a1a; color: #ddd; padding: 1.5rem 1rem; }
        .sidebar h1 { font-size: 1.125rem; margin: 0 0 1.5rem; color: #fff; }
        .sidebar nav a { display: block; padding: 0.5rem 0.75rem; color: #ccc; text-decoration: none; border-radius: 4px; margin-bottom: 0.25rem; font-size: 0.9rem; }
        .sidebar nav a:hover { background: #2a2a2a; color: #fff; }
        .sidebar nav a.active { background: #2a2a2a; color: #fff; font-weight: 600; }
        .topbar { grid-area: topbar; background: #fff; border-bottom: 1px solid #e0e0e0; display: flex; align-items: center; justify-content: flex-end; padding: 0 1.5rem; gap: 1rem; }
        .topbar .user { font-size: 0.9rem; color: #555; }
        .topbar form { display: inline; }
        .topbar button { background: none; border: 1px solid #ccc; padding: 0.4rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.875rem; }
        .main { grid-area: main; padding: 2rem; }
        .main h1 { margin-top: 0; }
        .card { background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 1.25rem; }
        .stat-card .label { font-size: 0.8rem; color: #888; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-card .value { font-size: 1.75rem; font-weight: 600; margin-top: 0.25rem; }
        .banner { background: #fff7e6; border: 1px solid #ffd591; padding: 1rem 1.5rem; border-radius: 6px; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; }
        .banner a { background: #fa8c16; color: #fff; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        th { background: #fafafa; font-weight: 600; }
        .btn { display: inline-block; padding: 0.5rem 1rem; background: #1677ff; color: #fff; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-size: 0.9rem; }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .btn-danger { background: #ff4d4f; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.8rem; }
        .form-row { margin-bottom: 1rem; }
        .form-row label { display: block; font-weight: 600; margin-bottom: 0.25rem; }
        .form-row input[type=text], .form-row input[type=email], .form-row input[type=url], .form-row select, .form-row textarea { width: 100%; padding: 0.5rem; font-size: 1rem; border: 1px solid #ccc; border-radius: 4px; }
        .form-row textarea { min-height: 120px; }
        .form-row .checkbox { display: inline-block; font-weight: normal; }
        .actions { display: flex; gap: 0.5rem; }
        .status { background: #efe; border: 1px solid #cec; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .errors { background: #fee; border: 1px solid #fcc; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .errors ul { margin: 0; padding-left: 1.25rem; }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <h1>Testimonial SaaS</h1>
            <nav>
                <a href="{{ route('dashboard.index') }}" class="{{ request()->routeIs('dashboard.index') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('dashboard.spaces.index') }}" class="{{ request()->routeIs('dashboard.spaces.*') ? 'active' : '' }}">Spaces</a>
                <a href="{{ route('dashboard.inbox.index') }}" class="{{ request()->routeIs('dashboard.inbox.*') || request()->routeIs('dashboard.testimonials.*') ? 'active' : '' }}">Inbox</a>
                <a href="{{ route('dashboard.billing.index') }}" class="{{ request()->routeIs('dashboard.billing.*') ? 'active' : '' }}">Billing</a>
            </nav>
        </aside>

        <header class="topbar">
            <span class="user">{{ $user->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Log out</button>
            </form>
        </header>

        <main class="main">
            @if (session('status'))
                <div class="status">{{ session('status') }}</div>
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
</body>
</html>
