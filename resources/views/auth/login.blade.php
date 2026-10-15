@extends('layouts.guest')

@section('title', 'Log in')

@section('content')
    <div class="guest-brand">
        <span class="brand-mark">💬</span>
        <span class="brand-text">TestimonialHub</span>
    </div>

    <div class="guest-header">
        <h1>Welcome back! 👋</h1>
        <p class="subtitle">Enter your credentials to access your dashboard.</p>
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

    <form class="guest-form" method="POST" action="{{ route('login') }}">
        @csrf

        <div class="field">
            <label for="email"><span class="ico">✉️</span> Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}"
                   placeholder="you@example.com" required autofocus autocomplete="username">
        </div>

        <div class="field">
            <label for="password"><span class="ico">🔑</span> Password</label>
            <input type="password" name="password" id="password"
                   placeholder="••••••••" required autocomplete="current-password">
        </div>

        <label class="remember">
            <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
            <span>Remember me</span>
        </label>

        <button type="submit">🚀 Log in</button>
    </form>

    <div class="guest-footer">
        <p><a href="{{ route('password.request') }}">Forgot your password?</a></p>
        <p>Don&apos;t have an account? <a href="{{ route('register') }}">Register</a></p>
    </div>
@endsection
