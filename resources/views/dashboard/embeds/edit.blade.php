@extends('layouts.dashboard')

@section('title', 'Edit embed — ' . $space->title)

@section('content')
    <h1>Edit embed</h1>

    <form method="POST" action="{{ route('dashboard.embeds.update', [$space, $config]) }}">
        @csrf
        @method('PATCH')
        @include('dashboard.embeds._form', ['config' => $config])
    </form>
@endsection
