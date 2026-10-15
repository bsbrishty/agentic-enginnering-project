@extends('layouts.dashboard')

@section('title', 'Spaces')

@section('content')
    <style>
        .spaces-empty {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--color-text-soft);
        }
        .spaces-empty .emoji { font-size: 2.5rem; display: block; margin-bottom: 0.5rem; }
        .spaces-table .space-title {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }
        .spaces-table .space-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px; height: 28px;
            border-radius: 8px;
            background: var(--gradient-primary);
            color: #fff;
            font-size: 0.85rem;
        }
        .spaces-table .count-pill {
            display: inline-block;
            background: var(--color-primary-soft);
            color: var(--color-primary-strong);
            padding: 0.15rem 0.6rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .spaces-table code {
            font-size: 0.8rem;
            background: var(--color-bg-soft);
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
        }
    </style>

    <div style="display: flex; align-items: center; justify-content: space-between;">
        <h1>🪐 {{ __('messages.spaces.title') }}</h1>
        <a href="{{ route('dashboard.spaces.create') }}" class="btn">➕ {{ __('messages.spaces.create') }}</a>
    </div>

    @if ($spaces->isEmpty())
        <div class="card">
            <div class="spaces-empty">
                <span class="emoji">🌌</span>
                <p style="margin: 0;">{{ __('messages.spaces.empty') }}</p>
                <p style="margin: 0.5rem 0 0;">
                    <a href="{{ route('dashboard.spaces.create') }}">{{ __('messages.spaces.create') }}</a> {{ __('messages.spaces.empty_create') }}
                </p>
            </div>
        </div>
    @else
        <div class="card">
            <table class="spaces-table">
                <thead>
                    <tr>
                        <th>{{ __('messages.spaces.header_title') }}</th>
                        <th>{{ __('messages.spaces.header_theme') }}</th>
                        <th>{{ __('messages.spaces.header_count') }}</th>
                        <th>{{ __('messages.spaces.header_url') }}</th>
                        <th>{{ __('messages.spaces.header_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($spaces as $space)
                        <tr>
                            <td>
                                <span class="space-title">
                                    <span class="space-icon">🪐</span>
                                    {{ $space->title }}
                                </span>
                            </td>
                            <td>{{ ucwords(str_replace('_', ' ', $space->theme)) }}</td>
                            <td><span class="count-pill">{{ $space->testimonials_count }}</span></td>
                            <td><code>/c/{{ $space->public_slug }}</code></td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('dashboard.spaces.show', $space) }}" class="btn btn-sm">👁️ View</a>
                                    <a href="{{ route('dashboard.spaces.edit', $space) }}" class="btn btn-sm btn-secondary">✏️ Edit</a>
                                    <a href="{{ route('dashboard.embeds.index', $space) }}" class="btn btn-sm btn-secondary">❤️ Wall of Love</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
