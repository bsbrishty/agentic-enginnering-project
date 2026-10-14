@extends('layouts.guest')

@section('title', 'Register')

@section('content')
    <h1>Create your account</h1>

    @if ($errors->any())
        <div class="errors">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <label for="name">Name</label>
        <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus>

        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" required>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <label for="password_confirmation">Confirm password</label>
        <input type="password" name="password_confirmation" id="password_confirmation" required>

        <button type="submit">Register</button>
    </form>

    <p class="alt">
        Already have an account? <a href="{{ route('login') }}">Log in</a>
    </p>
@endsection
