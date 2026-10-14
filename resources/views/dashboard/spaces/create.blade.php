@extends('layouts.dashboard')

@section('title', 'Create space')

@section('content')
    <h1>Create a space</h1>

    <form method="POST" action="{{ route('dashboard.spaces.store') }}">
        @csrf
        @include('dashboard.spaces._form', ['space' => new \App\Models\Space()])
    </form>
@endsection
