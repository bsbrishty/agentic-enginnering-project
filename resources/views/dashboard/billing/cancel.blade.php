@extends('layouts.dashboard')

@section('title', 'Checkout cancelled')

@section('content')
    <h1>Checkout cancelled</h1>

    <div class="card">
        <p>No charges were made. You can come back to upgrade whenever you're ready.</p>
        <p>
            <a href="{{ route('dashboard.billing.index') }}" class="btn">Back to billing</a>
        </p>
    </div>
@endsection
