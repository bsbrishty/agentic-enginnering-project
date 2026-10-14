<?php

namespace App\Providers;

use App\Models\Space;
use App\Models\Testimonial;
use App\Policies\SpacePolicy;
use App\Policies\TestimonialPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Map Eloquent models to their policy classes. The dashboard
        // controllers call $this->authorize(...) which uses these.
        Gate::policy(Space::class, SpacePolicy::class);
        Gate::policy(Testimonial::class, TestimonialPolicy::class);
    }
}
