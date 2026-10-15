@extends('layouts.dashboard')

@section('title', 'Inbox')

@section('content')
    <h1>💌 {{ __('messages.inbox.title') }}</h1>

    <style>
        /* Inbox-only styling on top of the dashboard layout primitives. */
        .inbox-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        .inbox-filters .filter-actions { display: flex; gap: 0.5rem; align-items: end; }
        .inbox-filters label { display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.25rem; color: var(--color-text-soft); }
        .inbox-filters select,
        .inbox-filters input[type=text] {
            width: 100%; padding: 0.55rem 0.7rem;
            font-size: 0.95rem; font-family: var(--font-body);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            background: #fff;
        }
        .inbox-filters select:focus,
        .inbox-filters input[type=text]:focus {
            outline: none; border-color: var(--color-primary);
            box-shadow: var(--shadow-glow);
        }

        .inbox-row td .row-name { font-weight: 600; }
        .inbox-row td .row-email { font-size: 0.75rem; color: var(--color-text-soft); }
        .inbox-row .rating { color: var(--color-warning); letter-spacing: 0.05em; }
        .inbox-row .rating-empty { color: var(--color-text-muted); }

        /* Consent pill */
        .consent-pill {
            display: inline-block;
            padding: 0.18rem 0.6rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .consent-granted  { background: var(--color-success-soft); color: #065f46; }
        .consent-declined { background: var(--color-danger-soft);  color: #991b1b; }
        .consent-not_asked { background: #f3f4f6; color: #4b5563; }

        /* Flag column */
        .inbox-flags { display: inline-flex; gap: 0.35rem; }
        .inbox-flags .flag { font-size: 1rem; line-height: 1; }
        .inbox-flags .flag-favorite { filter: drop-shadow(0 0 4px rgba(251, 191, 36, 0.4)); }
        .inbox-flags .flag-wall     { filter: drop-shadow(0 0 4px rgba(236, 72, 153, 0.4)); }
        .inbox-flags .flag-hidden   { filter: grayscale(1); opacity: 0.7; }

        /* Total line */
        .inbox-total { margin: 0 0 0.75rem; font-size: 0.85rem; color: var(--color-text-soft); }
    </style>

    <form method="GET" action="{{ route('dashboard.inbox.index') }}" class="card">
        <div class="inbox-filters">
            <div>
                <label for="filter-space">🪐 {{ __('messages.inbox.filter_space') }}</label>
                <select name="space_id" id="filter-space">
                    <option value="">All</option>
                    @foreach ($spaces as $s)
                        <option value="{{ $s->id }}" @selected(($filters['space_id'] ?? '') == $s->id)>{{ $s->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-consent">🔒 {{ __('messages.inbox.filter_consent') }}</label>
                <select name="consent_state" id="filter-consent">
                    <option value="">Any</option>
                    @foreach (['granted', 'declined', 'not_asked'] as $v)
                        <option value="{{ $v }}" @selected(($filters['consent_state'] ?? '') === $v)>{{ ucfirst($v) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-status">🚦 {{ __('messages.inbox.filter_status') }}</label>
                <select name="status" id="filter-status">
                    <option value="">Any</option>
                    <option value="favorite"      @selected(($filters['status'] ?? '') === 'favorite')>⭐ Favorited</option>
                    <option value="wall_of_love"  @selected(($filters['status'] ?? '') === 'wall_of_love')>❤️ On Wall of Love</option>
                    <option value="hidden"        @selected(($filters['status'] ?? '') === 'hidden')>🚫 Hidden</option>
                    <option value="visible"       @selected(($filters['status'] ?? '') === 'visible')>👁️ Visible</option>
                </select>
            </div>
            <div>
                <label for="filter-q">🔍 {{ __('messages.inbox.filter_search') }}</label>
                <input type="text" name="q" id="filter-q" value="{{ $filters['q'] ?? '' }}" placeholder="Text, name, or email">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn">{{ __('messages.inbox.filter_button') }}</button>
                <a href="{{ route('dashboard.inbox.index') }}" class="btn btn-secondary">{{ __('messages.inbox.filter_reset') }}</a>
            </div>
        </div>
    </form>

    @if ($testimonials->isEmpty())
        <div class="card">
            <p style="margin: 0; color: var(--color-text-soft);">📭 {{ __('messages.inbox.empty') }}</p>
        </div>
    @else
        <div class="card">
            <p class="inbox-total">
                ✨ {{ __('messages.inbox.total_found', ['n' => $testimonials->total()]) }}
            </p>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Space</th>
                        <th>Text</th>
                        <th>Rating</th>
                        <th>Consent</th>
                        <th>Flags</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($testimonials as $t)
                        <tr class="inbox-row">
                            <td>
                                <span class="row-name">{{ $t->submitter_name }}</span><br>
                                <span class="row-email">{{ $t->submitter_email }}</span>
                            </td>
                            <td>{{ $t->space->title ?? '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($t->testimonial_text, 80) }}</td>
                            <td>
                                @if (! is_null($t->rating))
                                    <span class="rating" title="{{ $t->rating }} / 5">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <span style="{{ $i <= $t->rating ? '' : 'opacity: 0.3;' }}">★</span>
                                        @endfor
                                    </span>
                                @else
                                    <span class="rating-empty">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="consent-pill consent-{{ $t->consent_state }}">
                                    @if ($t->consent_state === 'granted') ✓
                                    @elseif ($t->consent_state === 'declined') ✕
                                    @else ? @endif
                                    {{ ucfirst($t->consent_state) }}
                                </span>
                            </td>
                            <td>
                                <span class="inbox-flags">
                                    @if ($t->is_favorite) <span class="flag flag-favorite" title="Favorite">⭐</span> @endif
                                    @if ($t->is_wall_of_love) <span class="flag flag-wall" title="On Wall of Love">❤️</span> @endif
                                    @if ($t->is_hidden) <span class="flag flag-hidden" title="Hidden">🚫</span> @endif
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    {{-- Favorite toggle --}}
                                    <form method="POST" action="{{ route('dashboard.testimonials.update', $t) }}" style="display:inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="favorite">
                                        <button type="submit" class="btn btn-sm {{ $t->is_favorite ? '' : 'btn-secondary' }}"
                                                title="{{ $t->is_favorite ? 'Unfavorite' : 'Favorite' }}">
                                            {{ $t->is_favorite ? '⭐ Unfavorite' : '⭐ Favorite' }}
                                        </button>
                                    </form>

                                    {{-- Wall of Love toggle (consent-gated) --}}
                                    @if ($t->consent_state === 'granted')
                                        <form method="POST" action="{{ route('dashboard.testimonials.update', $t) }}" style="display:inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="wall_of_love">
                                            <button type="submit" class="btn btn-sm {{ $t->is_wall_of_love ? '' : 'btn-secondary' }}"
                                                    title="{{ $t->is_wall_of_love ? 'Remove from Wall of Love' : 'Add to Wall of Love' }}">
                                                {{ $t->is_wall_of_love ? '❤️ Remove WoL' : '❤️ Wall of Love' }}
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-sm btn-secondary" disabled
                                                title="Cannot add to Wall of Love: consent not granted">
                                            ❤️ Wall of Love
                                        </button>
                                    @endif

                                    {{-- Hide / Unhide toggle --}}
                                    <form method="POST" action="{{ route('dashboard.testimonials.update', $t) }}" style="display:inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="hidden">
                                        <button type="submit" class="btn btn-sm {{ $t->is_hidden ? '' : 'btn-secondary' }}"
                                                title="{{ $t->is_hidden ? 'Unhide' : 'Hide' }}">
                                            {{ $t->is_hidden ? '👁️ Unhide' : '👁️‍🗨️ Hide' }}
                                        </button>
                                    </form>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('dashboard.testimonials.destroy', $t) }}"
                                          onsubmit="return confirm('Delete this testimonial? This cannot be undone.')"
                                          style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">🗑️ Delete</button>
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
