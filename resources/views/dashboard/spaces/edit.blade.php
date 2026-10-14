@extends('layouts.dashboard')

@section('title', 'Edit space')

@section('content')
    <h1>Edit space</h1>

    <form method="POST" action="{{ route('dashboard.spaces.update', $space) }}">
        @csrf
        @method('PATCH')
        @include('dashboard.spaces._form', ['space' => $space])
    </form>
@endsection
