@extends('layouts.guest')

@section('title', 'Reset password')

@section('content')
    <div class="guest-brand">
        <span class="brand-mark">💬</span>
        <span class="brand-text">TestimonialHub</span>
    </div>

    <div class="guest-header">
        <h1>Reset your password 🔑</h1>
        <p class="subtitle">Choose a new password to regain access to your dashboard.</p>
    </div>

    @if ($errors->any())
        <div class="errors" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="guest-form" method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="field">
            <label for="email"><span class="ico">✉️</span> Email</label>
            <input type="email" name="email" id="email" value="{{ old('email', $request->input('email')) }}"
                   placeholder="you@example.com" required autofocus autocomplete="username">
        </div>

        <div class="field">
            <label for="password"><span class="ico">🔑</span> New password</label>
            <input type="password" name="password" id="password"
                   placeholder="At least 8 characters" required autocomplete="new-password">
        </div>

        <div class="field">
            <label for="password_confirmation"><span class="ico">✅</span> Confirm new password</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   placeholder="Repeat your new password" required autocomplete="new-password">
        </div>

        <button type="submit">🚀 Reset password</button>
    </form>

    <div class="guest-footer">
        <p><a href="{{ route('login') }}">← Back to log in</a></p>
    </div>
@endsection
