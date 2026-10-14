@extends('layouts.guest')

@section('title', 'Reset password')

@section('content')
    <h1>Reset your password</h1>

    @if ($errors->any())
        <div class="errors">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email', $request->input('email')) }}" required autofocus>

        <label for="password">New password</label>
        <input type="password" name="password" id="password" required>

        <label for="password_confirmation">Confirm new password</label>
        <input type="password" name="password_confirmation" id="password_confirmation" required>

        <button type="submit">Reset password</button>
    </form>
@endsection
