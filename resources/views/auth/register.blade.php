@extends('layouts.guest')

@section('title', 'Register')

@section('content')
    <div class="guest-brand">
        <span class="brand-mark">💬</span>
        <span class="brand-text">TestimonialHub</span>
    </div>

    <div class="guest-header">
        <h1>Create your account ✨</h1>
        <p class="subtitle">Start collecting beautiful testimonials in minutes.</p>
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

    <form class="guest-form" method="POST" action="{{ route('register') }}">
        @csrf

        <div class="field">
            <label for="name"><span class="ico">👤</span> Name</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}"
                   placeholder="Your full name" required autofocus autocomplete="name">
        </div>

        <div class="field">
            <label for="email"><span class="ico">✉️</span> Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}"
                   placeholder="you@example.com" required autocomplete="email">
        </div>

        <div class="field">
            <label for="password"><span class="ico">🔑</span> Password</label>
            <input type="password" name="password" id="password"
                   placeholder="At least 8 characters" required autocomplete="new-password">
        </div>

        <div class="field">
            <label for="password_confirmation"><span class="ico">✅</span> Confirm password</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   placeholder="Repeat your password" required autocomplete="new-password">
        </div>

        <button type="submit">🚀 Create account</button>
    </form>

    <div class="guest-footer">
        <p>Already have an account? <a href="{{ route('login') }}">Log in</a></p>
    </div>
@endsection
