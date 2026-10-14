@extends('layouts.dashboard')

@section('title', 'New embed — ' . $space->title)

@section('content')
    <h1>New embed for "{{ $space->title }}"</h1>

    <form method="POST" action="{{ route('dashboard.embeds.store', $space) }}">
        @csrf
        @include('dashboard.embeds._form', ['config' => null])
    </form>
@endsection
