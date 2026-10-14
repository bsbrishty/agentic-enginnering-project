<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Plan;
use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Services\PlanResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly PlanResolver $plans)
    {
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

        // Unique submitters: count distinct submitter_email_normalized.
        // The column is generated in MySQL; we can reference it directly.
        $uniqueSubmitters = Testimonial::query()
            ->whereHas('space', fn ($q) => $q->where('user_id', $user->id))
            ->distinct('submitter_email_normalized')
            ->count('submitter_email_normalized');

        $recentTestimonials = Testimonial::query()
            ->whereHas('space', fn ($q) => $q->where('user_id', $user->id))
            ->with('space:id,title,public_slug')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard.index', [
            'currentPlan' => $plan,
            'activeSpaces' => $activeSpaces,
            'spaceUsage' => ['used' => $activeSpaces, 'max' => $plan->maxSpaces()],
            'totalTestimonials' => $totalTestimonials,
            'uniqueSubmitters' => $uniqueSubmitters,
            'testimonialUsage' => ['used' => $totalTestimonials, 'max' => $plan->maxTestimonialsPerSpace() * max($activeSpaces, 1)],
            'recentTestimonials' => $recentTestimonials,
        ]);
    }
}
