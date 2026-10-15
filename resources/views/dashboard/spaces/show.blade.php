@extends('layouts.dashboard')

@section('title', $space->title)

@section('content')
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <h1>{{ $space->title }}</h1>
        <div class="actions">
            <a href="{{ route('dashboard.spaces.edit', $space) }}" class="btn btn-secondary">Edit</a>
            <a href="{{ route('dashboard.embeds.index', $space) }}" class="btn btn-secondary">Embeds</a>
            <form method="POST" action="{{ route('dashboard.spaces.destroy', $space) }}"
                  onsubmit="return confirm('Delete this space and all its testimonials? This cannot be undone.')"
                  style="display:inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h2>Public collection URL</h2>
        <p>Share this link with your customers to collect testimonials.</p>
        <p>
            <input type="text" readonly value="{{ url('/c/' . $space->public_slug) }}"
                   style="width: 100%; padding: 0.5rem; font-family: monospace;"
                   onclick="this.select()">
        </p>
        <p style="font-size: 0.8rem; color: #888;">
            The public_token is included in the form's hidden field for security; the URL itself uses the slug.
        </p>
    </div>

    <div class="card">
        <h2>Overview</h2>
        <table>
            <tbody>
                <tr><th>Testimonials</th><td>{{ $space->testimonials_count }}</td></tr>
                <tr><th>Theme</th><td>{{ ucwords(str_replace('_', ' ', $space->theme)) }}</td></tr>
                <tr><th>Star ratings</th><td>{{ $space->star_ratings_enabled ? 'Enabled' : 'Disabled' }}</td></tr>
                <tr><th>Accepting submissions</th><td>{{ $space->is_active ? 'Yes' : 'No' }}</td></tr>
                <tr><th>Embeds</th><td>{{ $embedConfigs->count() }}</td></tr>
            </tbody>
        </table>
    </div>

    @if ($embedConfigs->isNotEmpty())
        <div class="card">
            <h2>Embed widgets</h2>
            <table>
                <thead>
                    <tr><th>Layout</th><th>Theme</th><th>Background</th><th>Animation</th></tr>
                </thead>
                <tbody>
                    @foreach ($embedConfigs as $c)
                        <tr>
                            <td>{{ ucfirst($c->layout) }}</td>
                            <td>{{ ucfirst($c->theme_mode) }}</td>
                            <td><code>{{ $c->background_color }}</code></td>
                            <td>{{ $c->animation_enabled ? 'On' : 'Off' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
