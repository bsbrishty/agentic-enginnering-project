<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Plan;
use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Services\PlanResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(private readonly PlanResolver $plans)
    {
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
}
