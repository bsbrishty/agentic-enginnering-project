@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    <h1>Dashboard</h1>

    @if ($currentPlan === \App\Enums\Plan::Free)
        <div class="banner">
            <span>You're on the <strong>Free</strong> plan. Upgrade for more spaces and higher limits.</span>
            <a href="{{ route('dashboard.billing.index') }}">Upgrade</a>
        </div>
    @endif

    <div class="stat-grid">
        <div class="stat-card">
            <div class="label">Total testimonials</div>
            <div class="value">{{ $totalTestimonials }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Unique submitters</div>
            <div class="value">{{ $uniqueSubmitters }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Active spaces</div>
            <div class="value">{{ $activeSpaces }} / {{ $currentPlan->maxSpaces() }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Plan</div>
            <div class="value">{{ ucfirst($currentPlan->value) }}</div>
        </div>
    </div>

    <div class="card">
        <h2>Recent testimonials</h2>
        @if ($recentTestimonials->isEmpty())
            <p>No testimonials yet. Share a public collection URL to start collecting.</p>
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
                        <tr>
                            <td>{{ $t->submitter_name }}</td>
                            <td>{{ $t->space->title ?? '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($t->testimonial_text, 60) }}</td>
                            <td>{{ $t->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <h2>Quick links</h2>
        <div class="actions">
            <a href="{{ route('dashboard.spaces.create') }}" class="btn">Create a space</a>
            <a href="{{ route('dashboard.inbox.index') }}" class="btn btn-secondary">Open inbox</a>
            @if ($activeSpaces > 0)
                <a href="{{ route('dashboard.spaces.index') }}" class="btn btn-secondary">Manage spaces</a>
            @endif
        </div>
    </div>
@endsection
