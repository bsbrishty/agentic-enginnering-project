@extends('layouts.dashboard')

@section('title', 'Embeds — ' . $space->title)

@section('content')
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <h1>Embed widgets for "{{ $space->title }}"</h1>
        <a href="{{ route('dashboard.embeds.create', $space) }}" class="btn">New embed</a>
    </div>

    @if ($configs->isEmpty())
        <div class="card">
            <p>No embeds yet. <a href="{{ route('dashboard.embeds.create', $space) }}">Create one</a> to embed testimonials on your site.</p>
        </div>
    @else
        @foreach ($configs as $c)
            @php
                $snippet = '<iframe src="' . url('/embed/' . $c->public_id) . '" width="100%" height="600" frameborder="0"></iframe>';
            @endphp
            <div class="card">
                <h2>{{ ucfirst($c->layout) }} / {{ ucfirst($c->theme_mode) }} / {{ $c->background_color }}</h2>
                <p>
                    <label>Embed URL</label>
                    <input type="text" readonly value="{{ url('/embed/' . $c->public_id) }}"
                           style="width: 100%; padding: 0.5rem; font-family: monospace;"
                           onclick="this.select()">
                </p>
                <p>
                    <label>Snippet</label>
                    <textarea readonly rows="3" style="width: 100%; font-family: monospace;">{{ $snippet }}</textarea>
                </p>
                <div class="actions">
                    <a href="{{ route('dashboard.embeds.edit', [$space, $c]) }}" class="btn btn-sm">Edit</a>
                    <form method="POST" action="{{ route('dashboard.embeds.destroy', [$space, $c]) }}"
                          onsubmit="return confirm('Delete this embed?')"
                          style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        @endforeach
    @endif
@endsection
