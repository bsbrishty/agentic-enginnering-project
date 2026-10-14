@extends('layouts.dashboard')

@section('title', 'Upgrade complete')

@section('content')
    <h1>Thanks for upgrading!</h1>

    <div class="card">
        <p>Your payment was successful. We're confirming your subscription now&hellip;</p>
        <p>This usually takes a few seconds. Refresh this page in a moment to see your new Pro plan.</p>

        @if (! empty($sessionId))
            <p style="font-size: 0.8rem; color: #888;">
                Reference: <code>{{ $sessionId }}</code>
            </p>
        @endif

        <p>
            <a href="{{ route('dashboard.billing.index') }}" class="btn">Back to billing</a>
            <a href="{{ route('dashboard.index') }}" class="btn btn-secondary">Go to dashboard</a>
        </p>
    </div>
@endsection
