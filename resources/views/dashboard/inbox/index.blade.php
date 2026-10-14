@extends('layouts.dashboard')

@section('title', 'Inbox')

@section('content')
    <h1>Testimonial inbox</h1>

    <form method="GET" action="{{ route('dashboard.inbox.index') }}" class="card">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; align-items: end;">
            <div>
                <label>Space</label>
                <select name="space_id">
                    <option value="">All</option>
                    @foreach ($spaces as $s)
                        <option value="{{ $s->id }}" @selected(($filters['space_id'] ?? '') == $s->id)>{{ $s->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Consent</label>
                <select name="consent_state">
                    <option value="">Any</option>
                    @foreach (['granted', 'declined', 'not_asked'] as $v)
                        <option value="{{ $v }}" @selected(($filters['consent_state'] ?? '') === $v)>{{ ucfirst($v) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Status</label>
                <select name="is_favorite">
                    <option value="">Any</option>
                    <option value="1" @selected(($filters['is_favorite'] ?? '') === '1')>Favorited</option>
                </select>
            </div>
            <div>
                <label>Search</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Text, name, or email">
            </div>
            <div>
                <button type="submit" class="btn">Filter</button>
            </div>
        </div>
    </form>

    @if ($testimonials->isEmpty())
        <div class="card">
            <p>No testimonials match these filters.</p>
        </div>
    @else
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Space</th>
                        <th>Text</th>
                        <th>Consent</th>
                        <th>Flags</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($testimonials as $t)
                        <tr>
                            <td>{{ $t->submitter_name }}</td>
                            <td>{{ $t->space->title ?? '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($t->testimonial_text, 80) }}</td>
                            <td>{{ ucfirst($t->consent_state) }}</td>
                            <td>
                                @if ($t->is_favorite) ⭐ @endif
                                @if ($t->is_wall_of_love) ❤️ @endif
                                @if ($t->is_hidden) 🚫 @endif
                            </td>
                            <td>
                                <div class="actions">
                                    <form method="POST" action="{{ route('dashboard.testimonials.update', $t) }}" style="display:inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="favorite">
                                        <button type="submit" class="btn btn-sm btn-secondary">Favorite</button>
                                    </form>
                                    <form method="POST" action="{{ route('dashboard.testimonials.update', $t) }}" style="display:inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="wall_of_love">
                                        <button type="submit" class="btn btn-sm btn-secondary">WoL</button>
                                    </form>
                                    <form method="POST" action="{{ route('dashboard.testimonials.update', $t) }}" style="display:inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="hidden">
                                        <button type="submit" class="btn btn-sm btn-secondary">Hide</button>
                                    </form>
                                    <form method="POST" action="{{ route('dashboard.testimonials.destroy', $t) }}"
                                          onsubmit="return confirm('Delete this testimonial? This cannot be undone.')"
                                          style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>{{ $testimonials->links() }}</div>
    @endif
@endsection
