<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Plan;
use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Services\DashboardAnalyticsService;
use App\Services\PlanResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Allowed values for the ?range= chart filter. */
    private const CHART_RANGES = ['7d', '30d', 'all'];

    public function __construct(
        private readonly PlanResolver $plans,
        private readonly DashboardAnalyticsService $analytics,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $plan = $this->plans->resolve($user);

        $activeSpaces = $user->spaces()->count();

        // Total testimonials across all owner's spaces.
        $totalTestimonials = Testimonial::query()
            ->whereHas('space', fn ($q) => $q->where('user_id', $user->id))
            ->count();

        // Unique submitters: count distinct submitter emails across
        // ALL of the owner's testimonials (no Wall-of-Love or consent
        // filter — the dashboard metric reflects the total reach of
        // the space, including hidden and declined-consent rows).
        // We deduplicate on submitter_email directly; the generated
        // submitter_email_normalized column is not strictly necessary
        // for correctness here, but kept consistent with the
        // unique-submitter semantics on the public collection form.
        $uniqueSubmitters = Testimonial::query()
            ->whereHas('space', fn ($q) => $q->where('user_id', $user->id))
            ->whereNotNull('submitter_email')
            ->where('submitter_email', '!=', '')
            ->distinct()
            ->count('submitter_email');

        $recentTestimonials = Testimonial::query()
            ->whereHas('space', fn ($q) => $q->where('user_id', $user->id))
            ->with('space:id,title,public_slug')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Chart analytics (PRD §4): per-day testimonial counts for the
        // owner, filtered by ?range= (default 30d). Unknown values are
        // coerced to 30d in the service so the controller stays thin.
        $range = strtolower((string) $request->query('range', '30d'));
        if (! in_array($range, self::CHART_RANGES, true)) {
            $range = '30d';
        }
        $chart = $this->analytics->testimonialsPerDay($user, $range);

        return view('dashboard.index', [
            'currentPlan' => $plan,
            'activeSpaces' => $activeSpaces,
            'spaceUsage' => ['used' => $activeSpaces, 'max' => $plan->maxSpaces()],
            'totalTestimonials' => $totalTestimonials,
            'uniqueSubmitters' => $uniqueSubmitters,
            'testimonialUsage' => ['used' => $totalTestimonials, 'max' => $plan->maxTestimonialsPerSpace() * max($activeSpaces, 1)],
            'recentTestimonials' => $recentTestimonials,
            'chartLabels' => $chart['labels'],
            'chartData' => $chart['data'],
            'chartRange' => $chart['range'],
            'chartStart' => $chart['start'],
            'chartEnd' => $chart['end'],
        ]);
    }
}
