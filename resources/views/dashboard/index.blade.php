@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    <h1>🚀 {{ __('messages.dashboard.title') }}</h1>

    <style>
        /* Dashboard-only styles. Most layout primitives (.card, .stat-grid,
           .stat-card, .btn, .banner) live in the dashboard layout. */
        .chart-card .chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .chart-card .chart-header h2 { margin: 0; }
        .chart-card .chart-range { display: flex; gap: 0.25rem; }
        .chart-card .chart-wrap { position: relative; height: 240px; }
        .chart-card .chart-empty {
            color: var(--color-text-soft);
            margin: 0;
            padding: 2rem 1rem;
            text-align: center;
            font-size: 0.95rem;
        }

        /* Recent testimonials: keep the table but make the empty state
           friendlier. */
        .recent-empty {
            color: var(--color-text-soft);
            padding: 1.5rem 0;
            text-align: center;
            font-size: 0.95rem;
        }
        .recent-row td:first-child { font-weight: 600; }
        .recent-row .snippet { color: var(--color-text-soft); }

        /* Quick links card */
        .quick-links h2 { margin-top: 0; }
        .quick-links .actions { gap: 0.5rem; }
    </style>

    @if ($currentPlan === \App\Enums\Plan::Free)
        <div class="banner">
            <span>🎁 {{ __('messages.dashboard.free_banner') }}</span>
            <a href="{{ route('dashboard.billing.index') }}">{{ __('messages.dashboard.upgrade') }}</a>
        </div>
    @endif

    {{-- KPI stat cards: vibrant gradients + emoji icons (PRD §13). --}}
    <div class="stat-grid">
        <div class="stat-card gradient-blue">
            <div class="icon" aria-hidden="true">📈</div>
            <div class="label">{{ __('messages.dashboard.total_reviews') }}</div>
            <div class="value">{{ $totalTestimonials }}</div>
        </div>
        <div class="stat-card gradient-pink">
            <div class="icon" aria-hidden="true">👥</div>
            <div class="label">{{ __('messages.dashboard.submitters') }}</div>
            <div class="value">{{ $uniqueSubmitters }}</div>
        </div>
        <div class="stat-card gradient-purple">
            <div class="icon" aria-hidden="true">⚡</div>
            <div class="label">{{ __('messages.dashboard.active_spaces') }}</div>
            <div class="value">{{ $activeSpaces }} <span style="font-size: 1rem; opacity: 0.85; font-weight: 600;">/ {{ $currentPlan->maxSpaces() }}</span></div>
        </div>
        <div class="stat-card gradient-warning">
            <div class="icon" aria-hidden="true">💎</div>
            <div class="label">{{ __('messages.dashboard.current_plan') }}</div>
            <div class="value">{{ ucfirst($currentPlan->value) }}</div>
        </div>
    </div>

    {{-- Testimonials over time (PRD §4). Aggregated by DashboardAnalyticsService;
         ?range=7d|30d|all toggles the window. The active button is the
         primary blue, the others are secondary. The empty state hides the
         canvas + script so no JS runs when there's nothing to draw. --}}
    <div class="card chart-card">
        <div class="chart-header">
            <h2>📊 {{ __('messages.dashboard.chart_title') }}</h2>
            <div class="chart-range" role="group" aria-label="Date range">
                <a class="btn btn-sm {{ $chartRange === '7d' ? '' : 'btn-secondary' }}"
                   href="{{ route('dashboard.index', ['range' => '7d']) }}"
                   aria-pressed="{{ $chartRange === '7d' ? 'true' : 'false' }}">{{ __('messages.dashboard.chart_7d') }}</a>
                <a class="btn btn-sm {{ $chartRange === '30d' ? '' : 'btn-secondary' }}"
                   href="{{ route('dashboard.index', ['range' => '30d']) }}"
                   aria-pressed="{{ $chartRange === '30d' ? 'true' : 'false' }}">{{ __('messages.dashboard.chart_30d') }}</a>
                <a class="btn btn-sm {{ $chartRange === 'all' ? '' : 'btn-secondary' }}"
                   href="{{ route('dashboard.index', ['range' => 'all']) }}"
                   aria-pressed="{{ $chartRange === 'all' ? 'true' : 'false' }}">{{ __('messages.dashboard.chart_all') }}</a>
            </div>
        </div>

        @if (array_sum($chartData) === 0)
            <p class="chart-empty">🌱 {{ __('messages.dashboard.chart_empty') }}</p>
        @else
            <div class="chart-wrap">
                <canvas id="testimonialsChart" aria-label="Testimonials over time"></canvas>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
            <script>
                (function () {
                    const labels = @json($chartLabels);
                    const data = @json($chartData);
                    const ctx = document.getElementById('testimonialsChart');
                    if (! ctx || typeof Chart === 'undefined') { return; }
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Testimonials',
                                data: data,
                                tension: 0.35,
                                fill: true,
                                borderColor: '#1677ff',
                                backgroundColor: 'rgba(22, 119, 255, 0.12)',
                                pointRadius: 2,
                                pointHoverRadius: 5,
                                pointBackgroundColor: '#1677ff',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 1.5,
                                borderWidth: 2.5,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                    titleFont: { family: "'Inter', system-ui", weight: '600' },
                                    bodyFont: { family: "'Inter', system-ui" },
                                    padding: 10,
                                    cornerRadius: 8,
                                    callbacks: {
                                        title: function (items) { return '📅 ' + items[0].label; },
                                        label: function (item) {
                                            const n = item.parsed.y;
                                            return '✨ ' + n + ' testimonial' + (n === 1 ? '' : 's');
                                        },
                                    },
                                },
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { precision: 0, color: '#6b7280' },
                                    grid: { color: 'rgba(15, 23, 42, 0.06)' },
                                },
                                x: {
                                    ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10, color: '#6b7280' },
                                    grid: { display: false },
                                },
                            },
                        },
                    });
                })();
            </script>
        @endif
    </div>

    <div class="card">
        <h2>⭐ {{ __('messages.dashboard.recent_testimonials') }}</h2>
        @if ($recentTestimonials->isEmpty())
            <p class="recent-empty">💌 {{ __('messages.dashboard.recent_empty') }}</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Space</th>
                        <th>Snippet</th>
                        <th>Received</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentTestimonials as $t)
                        <tr class="recent-row">
                            <td>
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: var(--gradient-primary); color: #fff; font-size: 0.75rem; font-weight: 600; margin-right: 0.5rem;">
                                    {{ strtoupper(mb_substr($t->submitter_name ?? 'A', 0, 1)) }}
                                </span>
                                {{ $t->submitter_name }}
                            </td>
                            <td>{{ $t->space->title ?? '—' }}</td>
                            <td class="snippet">{{ \Illuminate\Support\Str::limit($t->testimonial_text, 60) }}</td>
                            <td>{{ $t->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card quick-links">
        <h2>⚡ {{ __('messages.dashboard.quick_links') }}</h2>
        <div class="actions">
            <a href="{{ route('dashboard.spaces.create') }}" class="btn">➕ {{ __('messages.dashboard.create_space') }}</a>
            <a href="{{ route('dashboard.inbox.index') }}" class="btn btn-secondary">📨 {{ __('messages.dashboard.open_inbox') }}</a>
            @if ($activeSpaces > 0)
                <a href="{{ route('dashboard.spaces.index') }}" class="btn btn-secondary">🪐 {{ __('messages.dashboard.manage_spaces') }}</a>
            @endif
        </div>
    </div>
@endsection
