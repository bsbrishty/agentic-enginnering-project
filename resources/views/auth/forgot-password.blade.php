@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
    <div class="guest-brand">
        <span class="brand-mark">💬</span>
        <span class="brand-text">TestimonialHub</span>
    </div>

    <div class="guest-header">
        <h1>Forgot your password? 🔒</h1>
        <p class="subtitle">Enter your email and we&apos;ll send you a reset link.</p>
    </div>

    @if (session('status'))
        <div class="status" role="status">🎉 {{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="errors" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="guest-form" method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="field">
            <label for="email"><span class="ico">✉️</span> Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}"
                   placeholder="you@example.com" required autofocus autocomplete="username">
        </div>

        <button type="submit">📨 Email password reset link</button>
    </form>

    <div class="guest-footer">
        <p><a href="{{ route('login') }}">← Back to log in</a></p>
    </div>
@endsection
