@extends('layouts.guest')

@section('title', 'Log in')

@section('content')
    <h1>Log in</h1>

    @if ($errors->any())
        <div class="errors">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <label>
            <input type="checkbox" name="remember" value="1"> Remember me
        </label>

        <button type="submit">Log in</button>
    </form>

    <p class="alt">
        <a href="{{ route('password.request') }}">Forgot your password?</a><br>
        Don't have an account? <a href="{{ route('register') }}">Register</a>
    </p>
@endsection
