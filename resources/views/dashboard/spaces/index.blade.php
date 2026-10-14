@extends('layouts.dashboard')

@section('title', 'Spaces')

@section('content')
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <h1>Spaces</h1>
        <a href="{{ route('dashboard.spaces.create') }}" class="btn">Create a space</a>
    </div>

    @if ($spaces->isEmpty())
        <div class="card">
            <p>You don't have any spaces yet. <a href="{{ route('dashboard.spaces.create') }}">Create one</a> to start collecting testimonials.</p>
        </div>
    @else
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Theme</th>
                        <th>Testimonials</th>
                        <th>Public URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($spaces as $space)
                        <tr>
                            <td>{{ $space->title }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $space->theme)) }}</td>
                            <td>{{ $space->testimonials_count }}</td>
                            <td><code>/c/{{ $space->public_slug }}</code></td>
                            <td>
                                <a href="{{ route('dashboard.spaces.show', $space) }}" class="btn btn-sm">View</a>
                                <a href="{{ route('dashboard.spaces.edit', $space) }}" class="btn btn-sm btn-secondary">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
