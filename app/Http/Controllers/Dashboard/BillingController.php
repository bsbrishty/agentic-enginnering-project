<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Plan;
use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Services\PlanResolver;
use App\Services\StripeCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(
        private readonly PlanResolver $plans,
        private readonly StripeCheckoutService $checkout,
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
        ]);
    }

    /**
     * Create a Stripe Checkout session for the Pro plan and redirect.
     *
     * Refuses to redirect for users who are already on Pro.
     * Does NOT mutate the local database; the webhook (next change)
     * is what actually creates the `subscriptions` row and updates
     * `subscriptions.plan`.
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

        $session = $this->checkout->createForUser($user);

        return redirect()->away($session->url);
    }

    /**
     * Success return URL. Purely presentational. The actual plan change
     * happens when the webhook handler processes
     * `customer.subscription.created`.
     */
    public function success(Request $request): View
    {
        return view('dashboard.billing.success', [
            'sessionId' => $request->query('session_id'),
        ]);
    }

    /**
     * Cancel return URL. Purely presentational.
     */
    public function cancel(Request $request): View
    {
        return view('dashboard.billing.cancel');
    }
}
