<?php

namespace Tests\Feature;

use App\Models\Space;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * PRD §4: Dashboard chart & analytics.
 *
 * Covers:
 *   - chart renders with the default 30d window
 *   - ?range=7d / ?range=30d / ?range=all filter the window
 *   - invalid range falls back to 30d
 *   - chart only includes data for the authenticated user (cross-tenant)
 *   - missing dates are zero-filled within the window
 *   - empty state when the user has no testimonials
 *   - the service itself is auth-scoped (unit-level)
 */
class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function actingOwner(): User
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        return $owner;
    }

    public function test_dashboard_renders_chart_card_with_default_range(): void
    {
        $owner = $this->actingOwner();
        $space = Space::factory()->for($owner)->create();
        Testimonial::factory()->for($space)->create(['created_at' => now()]);

        $this->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Testimonials over time')
            ->assertSee('testimonialsChart')
            ->assertSee('7 Days')
            ->assertSee('30 Days')
            ->assertSee('All Time');
    }

    public function test_default_range_is_thirty_days(): void
    {
        $owner = $this->actingOwner();
        $space = Space::factory()->for($owner)->create();
        Testimonial::factory()->for($space)->count(3)->create(['created_at' => now()]);

        $response = $this->get(route('dashboard.index'))->assertOk();
        $this->assertCount(30, $response->viewData('chartData'));
        $this->assertCount(30, $response->viewData('chartLabels'));
        $this->assertSame('30d', $response->viewData('chartRange'));
    }

    public function test_range_query_param_filters_window(): void
    {
        $owner = $this->actingOwner();
        $space = Space::factory()->for($owner)->create();
        // 3 today, 1 ten days ago, 1 forty days ago.
        Testimonial::factory()->for($space)->count(3)->create(['created_at' => now()]);
        Testimonial::factory()->for($space)->create(['created_at' => now()->subDays(10)]);
        Testimonial::factory()->for($space)->create(['created_at' => now()->subDays(40)]);

        $seven = $this->get(route('dashboard.index', ['range' => '7d']))->assertOk();
        $this->assertCount(7, $seven->viewData('chartData'));
        $this->assertSame(3, array_sum($seven->viewData('chartData')));
        $this->assertSame('7d', $seven->viewData('chartRange'));

        $all = $this->get(route('dashboard.index', ['range' => 'all']))->assertOk();
        $this->assertSame(5, array_sum($all->viewData('chartData')));
        $this->assertSame('all', $all->viewData('chartRange'));
    }

    public function test_invalid_range_falls_back_to_thirty_days(): void
    {
        $owner = $this->actingOwner();

        $response = $this->get(route('dashboard.index', ['range' => 'bogus']))
            ->assertOk()
            ->assertViewIs('dashboard.index');

        $this->assertSame('30d', $response->viewData('chartRange'));
        $this->assertCount(30, $response->viewData('chartData'));
    }

    public function test_chart_only_includes_authenticated_users_spaces(): void
    {
        $owner = $this->actingOwner();
        $space = Space::factory()->for($owner)->create();
        Testimonial::factory()->for($space)->count(2)->create(['created_at' => now()]);

        $other = User::factory()->create();
        $otherSpace = Space::factory()->for($other)->create();
        Testimonial::factory()->for($otherSpace)->count(5)->create(['created_at' => now()]);

        $response = $this->get(route('dashboard.index'))->assertOk();
        $this->assertSame(2, array_sum($response->viewData('chartData')));
    }

    public function test_chart_zero_fills_missing_dates(): void
    {
        $owner = $this->actingOwner();
        $space = Space::factory()->for($owner)->create();
        // Two testimonials within the 7-day window: today and 5 days back.
        Testimonial::factory()->for($space)->create(['created_at' => now()]);
        Testimonial::factory()->for($space)->create(['created_at' => now()->subDays(5)]);

        $response = $this->get(route('dashboard.index', ['range' => '7d']))->assertOk();
        $data = $response->viewData('chartData');
        $labels = $response->viewData('chartLabels');

        $this->assertCount(7, $data);
        $this->assertCount(7, $labels);
        // Labels are YYYY-MM-DD strings, oldest first, today last.
        $this->assertSame(now()->subDays(6)->toDateString(), $labels[0]);
        $this->assertSame(now()->toDateString(), $labels[6]);
        // The "today" testimonial is at the last index; the "5 days back"
        // testimonial is at index 1 (subDays(6 - 5) = subDays(1) from start).
        $this->assertSame(0, $data[0]);
        $this->assertGreaterThan(0, $data[1]);
        $this->assertSame(0, $data[2]);
        $this->assertSame(0, $data[3]);
        $this->assertSame(0, $data[4]);
        $this->assertSame(0, $data[5]);
        $this->assertGreaterThan(0, $data[6]);
    }

    public function test_empty_state_renders_when_no_testimonials(): void
    {
        $owner = $this->actingOwner();
        Space::factory()->for($owner)->create();

        $this->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('No testimonials yet')
            ->assertDontSee('testimonialsChart');
    }

    public function test_service_only_returns_owned_spaces(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $s1 = Space::factory()->for($owner)->create();
        $s2 = Space::factory()->for($stranger)->create();
        Testimonial::factory()->for($s1)->count(2)->create(['created_at' => now()]);
        Testimonial::factory()->for($s2)->count(7)->create(['created_at' => now()]);

        /** @var DashboardAnalyticsService $service */
        $service = app(DashboardAnalyticsService::class);
        $chart = $service->testimonialsPerDay($owner, '7d');

        $this->assertCount(7, $chart['data']);
        $this->assertSame(2, array_sum($chart['data']));
        $this->assertSame('7d', $chart['range']);
    }

    public function test_chart_start_and_end_dates_are_inclusive(): void
    {
        $owner = $this->actingOwner();
        $space = Space::factory()->for($owner)->create();
        // Pin a far-back testimonial to verify the 7d range covers today.
        Carbon::setTestNow(now());
        Testimonial::factory()->for($space)->create(['created_at' => now()]);

        $response = $this->get(route('dashboard.index', ['range' => '7d']))->assertOk();
        $this->assertSame(now()->subDays(6)->toDateString(), $response->viewData('chartStart'));
        $this->assertSame(now()->toDateString(), $response->viewData('chartEnd'));

        Carbon::setTestNow();
    }
}
