<?php

namespace Tests\Feature;

use App\Models\Space;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the "Submitters" KPI on the dashboard.
 *
 * The metric must count distinct submitter emails across ALL of the
 * authenticated user's testimonials — including rows that are hidden,
 * declined-consent, or off the Wall of Love — because the KPI is a
 * measure of reach, not publication status.
 *
 * Also verifies cross-tenant isolation: a testimonial from another
 * user's space must NOT be counted.
 */
class DashboardUniqueSubmittersTest extends TestCase
{
    use RefreshDatabase;

    private function actingOwner(): User
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        return $owner;
    }

    public function test_dashboard_counts_two_distinct_submitters_maafi_and_moondas(): void
    {
        $owner = $this->actingOwner();
        $space = Space::factory()->for($owner)->create();

        Testimonial::factory()->for($space)->create([
            'submitter_email' => 'maafi@gmail.com',
        ]);
        Testimonial::factory()->for($space)->create([
            'submitter_email' => 'moondas@gmail.com',
        ]);

        $response = $this->get(route('dashboard.index'))->assertOk();

        // The "Submitters" stat-card value should be 2.
        $this->assertSame(2, $response->viewData('uniqueSubmitters'));
    }

    public function test_dashboard_counts_unique_email_only_once_when_same_submitter_posts_twice(): void
    {
        $owner = $this->actingOwner();
        $space = Space::factory()->for($owner)->create();

        // Same email submitted twice — still 1 unique submitter.
        Testimonial::factory()->for($space)->create(['submitter_email' => 'repeat@example.com']);
        Testimonial::factory()->for($space)->create(['submitter_email' => 'repeat@example.com']);
        Testimonial::factory()->for($space)->create(['submitter_email' => 'other@example.com']);

        $response = $this->get(route('dashboard.index'))->assertOk();
        $this->assertSame(2, $response->viewData('uniqueSubmitters'));
    }

    public function test_dashboard_includes_hidden_and_declined_consent_testimonials_in_submitter_count(): void
    {
        $owner = $this->actingOwner();
        $space = Space::factory()->for($owner)->create();

        // Off the Wall of Love
        Testimonial::factory()->for($space)->create([
            'submitter_email' => 'no-wall@example.com',
            'is_wall_of_love' => false,
        ]);
        // Declined consent
        Testimonial::factory()->for($space)->declinedConsent()->create([
            'submitter_email' => 'declined@example.com',
        ]);
        // Hidden by the owner
        Testimonial::factory()->for($space)->create([
            'submitter_email' => 'hidden@example.com',
            'is_hidden' => true,
        ]);

        $response = $this->get(route('dashboard.index'))->assertOk();
        $this->assertSame(3, $response->viewData('uniqueSubmitters'));
    }

    public function test_dashboard_does_not_count_other_users_submitters(): void
    {
        $owner = $this->actingOwner();
        $mySpace = Space::factory()->for($owner)->create();
        Testimonial::factory()->for($mySpace)->create(['submitter_email' => 'mine@example.com']);

        // Another user's space/testimonial — must NOT be counted.
        $other = User::factory()->create();
        $theirSpace = Space::factory()->for($other)->create();
        Testimonial::factory()->for($theirSpace)->create(['submitter_email' => 'theirs@example.com']);

        $response = $this->get(route('dashboard.index'))->assertOk();
        $this->assertSame(1, $response->viewData('uniqueSubmitters'));
    }

    public function test_dashboard_renders_submitters_label_for_kpi_card(): void
    {
        $owner = $this->actingOwner();
        Space::factory()->for($owner)->create();
        Testimonial::factory()->for(Space::factory()->for($owner)->create())
            ->create(['submitter_email' => 'maafi@gmail.com']);
        Testimonial::factory()->for(Space::factory()->for($owner)->create())
            ->create(['submitter_email' => 'moondas@gmail.com']);

        // The KPI card with the value 2 must be in the rendered HTML.
        $this->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Submitters')
            ->assertSeeInOrder([
                'Submitters',
                '<div class="value">2</div>',
            ]);
    }
}