<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Plan;
use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Services\PlanResolver;
use App\Services\SimulatedUpgradeService;
use App\Services\StripeCheckoutService;
use Illuminate\Contracts\View\View as ContractView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    /** Stripe Pro plan list price. Surfaced to the UI for display. */
    private const PRO_MONTHLY_PRICE_USD = 99;

    public function __construct(
        private readonly PlanResolver $plans,
        private readonly StripeCheckoutService $checkout,
        private readonly SimulatedUpgradeService $simulator,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $plan = $this->plans->resolve($user);

        $spacesUsed = $user->spaces()->count();
        $testimonialsUsed = Testimonial::query()
            ->whereHas('space', fn ($q) => $q->where('user_id', $user->id))
            ->count();

        return view('dashboard.billing.index', [
            'currentPlan' => $plan,
            'spacesUsed' => $spacesUsed,
            'testimonialsUsed' => $testimonialsUsed,
            'stripeReady' => (bool) config('stripe.pro_price_id'),
            'simulatedMode' => $this->simulator->isAllowed()
                && ($this->simulator->hasPlaceholderKeys()
                    || ! $this->hasRealStripeKeys()),
            'proPriceUsd' => self::PRO_MONTHLY_PRICE_USD,
        ]);
    }

    /**
     * Create a Stripe Checkout session for the Pro plan and redirect.
     *
     * Routing logic:
     *   1. Already on Pro → friendly redirect.
     *   2. Simulated mode (placeholders / no real keys / explicit mock) →
     *      `simulate()` upgrades the user locally and redirects to the
     *      success page. This is the unblocked dev experience.
     *   3. Real Stripe keys → real Checkout session.
     *   4. Otherwise → friendly "billing is not configured" error.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $user = $request->user();
        $plan = $this->plans->resolve($user);

        if ($plan === Plan::Pro) {
            return redirect()
                ->route('dashboard.billing.index')
                ->with('status', "You're already on Pro.");
        }

        if ($this->shouldUseSimulatedCheckout()) {
            $this->simulator->upgrade($user);
            return redirect()
                ->route('dashboard.billing.success')
                ->with('status', 'Welcome to Pro! (simulated checkout)');
        }

        if (! config('stripe.pro_price_id')) {
            return redirect()
                ->route('dashboard.billing.index')
                ->withErrors([
                    'stripe' => 'Stripe billing is not configured yet. Please contact the site administrator.',
                ]);
        }

        try {
            $session = $this->checkout->createForUser($user);
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('dashboard.billing.index')
                ->withErrors([
                    'stripe' => 'We could not start your upgrade right now. Please try again in a moment.',
                ]);
        }

        return redirect()->away($session->url);
    }

    /**
     * POST endpoint that runs the simulated upgrade without going through
     * the GET /checkout redirect (some browsers cache redirects). Idempotent.
     */
    public function simulate(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $this->simulator->isAllowed()) {
            abort(403, 'Simulated upgrades are disabled in this environment.');
        }

        $this->simulator->upgrade($user);

        return redirect()
            ->route('dashboard.billing.success')
            ->with('status', 'Welcome to Pro! (simulated checkout)');
    }

    /**
     * POST endpoint that cancels a simulated Pro subscription back to Free.
     * No-op in production (returns 403).
     */
    public function cancelSimulated(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $this->simulator->isAllowed()) {
            abort(403, 'Simulated upgrades are disabled in this environment.');
        }

        $cancelled = $this->simulator->cancel($user);

        return redirect()
            ->route('dashboard.billing.index')
            ->with(
                'status',
                $cancelled
                    ? 'Subscription cancelled. You are back on the Free plan.'
                    : 'No active subscription to cancel.'
            );
    }

    /**
     * Success return URL. Purely presentational. The actual plan change
     * happens when the webhook handler processes
     * `customer.subscription.created`.
     */
    public function success(Request $request): ContractView
    {
        return view('dashboard.billing.success', [
            'sessionId' => $request->query('session_id'),
        ]);
    }

    /**
     * Cancel return URL. Purely presentational.
     */
    public function cancel(Request $request): ContractView
    {
        return view('dashboard.billing.cancel');
    }

    /**
     * Decide whether the controller should bypass the real Stripe API and
     * run the local upgrade simulator. True when:
     *   - the simulator is allowed in this environment, AND
     *   - `pro_price_id` is set to the placeholder, AND
     *   - the keys are placeholders (or no real key looks like a real
     *     Stripe key, e.g. `pk_test_…` / `sk_test_…`).
     */
    private function shouldUseSimulatedCheckout(): bool
    {
        if (! $this->simulator->isAllowed()) {
            return false;
        }

        if (! $this->simulator->hasPlaceholderKeys()) {
            return false;
        }

        return true;
    }

    /**
     * Heuristic: a "real" Stripe key has the `pk_test_` / `sk_test_` /
     * `pk_live_` / `sk_live_` prefix and is longer than the placeholder.
     */
    private function hasRealStripeKeys(): bool
    {
        $key = (string) config('stripe.key', '');
        $secret = (string) config('stripe.secret', '');

        $looksReal = function (string $value, string $prefix): bool {
            return str_starts_with($value, $prefix) && strlen($value) > strlen($prefix) + 8;
        };

        return $looksReal($key, 'pk_test_')
            || $looksReal($key, 'pk_live_')
            || $looksReal($secret, 'sk_test_')
            || $looksReal($secret, 'sk_live_');
    }
}
