@extends('layouts.dashboard')

@section('title', 'Billing')

@section('content')
    <h1>Billing</h1>

    <div class="card">
        <h2>Current plan</h2>
        <p style="font-size: 1.5rem;">
            <strong>{{ ucfirst($currentPlan->value) }}</strong>
            @if ($currentPlan === \App\Enums\Plan::Pro)
                — $99/month
            @else
                — Free
            @endif
        </p>

        <table>
            <tbody>
                <tr>
                    <th>Spaces</th>
                    <td>{{ $spacesUsed }} / {{ $currentPlan->maxSpaces() }}</td>
                </tr>
                <tr>
                    <th>Testimonials per space</th>
                    <td>Up to {{ $currentPlan->maxTestimonialsPerSpace() }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if ($currentPlan === \App\Enums\Plan::Free)
        <div class="card">
            <h2>Upgrade to Pro</h2>
            <p>Pro gives you 25 spaces (vs 3) and 1,000 testimonials per space (vs 100).</p>
            <a href="{{ route('dashboard.billing.checkout') }}" class="btn">Upgrade to Pro — $99/month</a>
        </div>
    @else
        <div class="card">
            <h2>You're on Pro</h2>
            <p>Thank you! Manage your subscription via the Stripe customer portal.</p>
        </div>
    @endif
@endsection
