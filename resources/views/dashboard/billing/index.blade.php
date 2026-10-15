@extends('layouts.dashboard')

@section('title', 'Billing')

@section('content')
    <h1>Billing</h1>

    <style>
        /* Pricing matrix (PRD §9). Re-uses the .card style from the
           dashboard layout; only adds the pricing grid + per-plan card
           visuals scoped under .billing. */
        .billing .price-tag {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }
        .billing .price-tag .per { font-size: 0.95rem; color: #666; font-weight: 400; }
        .billing .plan-card {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 1.25rem;
            background: #fafafa;
        }
        .billing .plan-card.current {
            background: #f0f7ff;
            border-color: #91caff;
        }
        .billing .plan-card h3 { margin: 0 0 0.5rem; }
        .billing .plan-card ul { margin: 0.5rem 0 1rem 1rem; padding: 0; }
        .billing .plan-card li { margin-bottom: 0.25rem; }
        .billing .plan-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 600px) { .billing .plan-grid { grid-template-columns: 1fr; } }
        .billing .usage-bar {
            position: relative;
            background: #eee;
            border-radius: 4px;
            height: 8px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        .billing .usage-bar > span {
            display: block;
            height: 100%;
            background: #1677ff;
        }
        .billing .dev-banner {
            background: #fffbe6;
            border: 1px solid #ffe58f;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .billing .dev-banner code { background: #fff; padding: 1px 4px; border-radius: 3px; }
    </style>

    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    {{-- Dev-mode banner: makes the simulated flow obvious so a developer
         is never confused about why no real Stripe redirect happened. --}}
    @if (! empty($simulatedMode))
        <div class="dev-banner">
            <strong>Dev mode:</strong>
            Stripe keys are placeholders. The
            &ldquo;Upgrade to Pro&rdquo; button below simulates a successful
            checkout locally. Replace <code>STRIPE_KEY</code>,
            <code>STRIPE_SECRET</code>, and <code>STRIPE_PRO_PRICE_ID</code>
            in <code>.env</code> with real Stripe test keys to use the live
            Checkout flow.
        </div>
    @endif

    <div class="billing">
        <div class="card">
            <h2>{{ __('messages.billing.current_plan') }}</h2>
            <p class="price-tag">
                <strong>{{ $currentPlan === \App\Enums\Plan::Pro ? __('messages.billing.pro') : __('messages.billing.free') }}</strong>
                @if ($currentPlan === \App\Enums\Plan::Pro)
                    &mdash; ${{ $proPriceUsd }}<span class="per">{{ __('messages.billing.per_month') }}</span>
                @else
                    &mdash; {{ __('messages.billing.free') }}
                @endif
            </p>

            <table>
                <tbody>
                    <tr>
                        <th style="width: 200px;">{{ __('messages.billing.spaces_label') }}</th>
                        <td>
                            {{ $spacesUsed }} / {{ $currentPlan->maxSpaces() }}
                            <div class="usage-bar" aria-hidden="true">
                                @php
                                    $pct = $currentPlan->maxSpaces() > 0
                                        ? min(100, (int) round($spacesUsed / $currentPlan->maxSpaces() * 100))
                                        : 0;
                                @endphp
                                <span style="width: {{ $pct }}%;"></span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>{{ __('messages.billing.testimonials_per_space') }}</th>
                        <td>{{ __('messages.billing.testimonials_limit', ['n' => $currentPlan->maxTestimonialsPerSpace()]) }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('messages.billing.total_testimonials') }}</th>
                        <td>{{ $testimonialsUsed }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="plan-grid">
            {{-- Free plan card --}}
            <div class="plan-card {{ $currentPlan === \App\Enums\Plan::Free ? 'current' : '' }}">
                <h3>{{ __('messages.billing.free') }}</h3>
                <p class="price-tag">$0<span class="per">{{ __('messages.billing.per_month') }}</span></p>
                <ul>
                    <li>{{ __('messages.billing.up_to_spaces', ['n' => \App\Enums\Plan::Free->maxSpaces()]) }}</li>
                    <li>{{ __('messages.billing.testimonials_count', ['n' => \App\Enums\Plan::Free->maxTestimonialsPerSpace()]) }}</li>
                    <li>{{ __('messages.billing.public_form') }}</li>
                    <li>{{ __('messages.billing.inbox_widget') }}</li>
                </ul>
                @if ($currentPlan === \App\Enums\Plan::Free)
                    <p style="font-size: 0.85rem; color: #1677ff;"><strong>{{ __('messages.billing.current_plan_marker') }}</strong></p>
                @endif
            </div>

            {{-- Pro plan card --}}
            <div class="plan-card {{ $currentPlan === \App\Enums\Plan::Pro ? 'current' : '' }}">
                <h3>{{ __('messages.billing.pro') }}</h3>
                <p class="price-tag">${{ $proPriceUsd }}<span class="per">{{ __('messages.billing.per_month') }}</span></p>
                <ul>
                    <li>{{ __('messages.billing.up_to_spaces', ['n' => \App\Enums\Plan::Pro->maxSpaces()]) }}</li>
                    <li>{{ __('messages.billing.testimonials_count', ['n' => \App\Enums\Plan::Pro->maxTestimonialsPerSpace()]) }}</li>
                    <li>{{ __('messages.billing.public_form') }}</li>
                    <li>{{ __('messages.billing.inbox_widget') }}</li>
                    <li>{{ __('messages.billing.priority_support') }}</li>
                </ul>

                @if ($currentPlan === \App\Enums\Plan::Pro)
                    <p style="font-size: 0.85rem; color: #1677ff;"><strong>{{ __('messages.billing.current_plan_marker') }}</strong></p>

                    @if (! empty($simulatedMode))
                        <form method="POST" action="{{ route('dashboard.billing.cancel-simulated') }}"
                              onsubmit="return confirm('Cancel your simulated Pro subscription?');">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm">
                                {{ __('messages.billing.cancel_simulated') }}
                            </button>
                        </form>
                    @endif
                @else
                    @if (! empty($stripeReady))
                        @if (! empty($simulatedMode))
                            <p style="font-size: 0.85rem; color: #666; margin-bottom: 0.5rem;">
                                Dev mode: the button below upgrades you instantly without contacting Stripe.
                            </p>
                            <form method="POST" action="{{ route('dashboard.billing.simulate') }}">
                                @csrf
                                <button type="submit" class="btn">
                                    {{ __('messages.billing.upgrade_button_sim', ['price' => $proPriceUsd]) }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('dashboard.billing.checkout') }}" class="btn">
                                {{ __('messages.billing.upgrade_button', ['price' => $proPriceUsd]) }}
                            </a>
                        @endif
                    @else
                        <p style="color: #c00;">{{ __('messages.billing.stripe_not_configured') }}</p>
                        <p style="font-size: 0.85rem; color: #666;">
                            {{ __('messages.billing.stripe_set_price_id') }}
                        </p>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
