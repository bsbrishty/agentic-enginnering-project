@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
    <h1>Forgot your password?</h1>
    <p>Enter your email and we'll send you a reset link.</p>

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

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>

        <button type="submit">Email password reset link</button>
    </form>

    <p class="alt">
        <a href="{{ route('login') }}">Back to log in</a>
    </p>
@endsection
