<?php

namespace Tests\Feature;

use App\Enums\Plan;
use App\Models\Space;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD §5–§6: Public collection form, submission, and confirmation.
 */
class PublicSpaceSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_form_renders_with_safe_fields_config(): void
    {
        $user = User::factory()->create();
        // Force an empty fields_config to ensure the view handles it safely.
        $space = Space::factory()->for($user)->create(['fields_config' => []]);

        $response = $this->get('/c/' . $space->public_slug);

        $response->assertOk();
        $response->assertSee('Submit testimonial');
        // The default fields are populated as a fallback.
        $response->assertSee('Name');
        $response->assertSee('Email');
        $response->assertSee('Testimonial Text');
    }

    public function test_inactive_space_does_not_render_form(): void
    {
        $user = User::factory()->create();
        $space = Space::factory()->inactive()->for($user)->create();

        $response = $this->get('/c/' . $space->public_slug);

        $response->assertOk();
        $response->assertSee('not accepting submissions');
    }

    public function test_successful_submission_shows_confirmation(): void
    {
        $user = User::factory()->create();
        $space = Space::factory()->for($user)->create();

        $response = $this->post('/c/' . $space->public_slug, [
            'public_token' => $space->public_token,
            'submitter_name' => 'Jane Doe',
            'submitter_email' => 'jane@example.com',
            'testimonial_text' => 'Great experience!',
            'rating' => 5,
            'consent_state' => 'granted',
            'field_values' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'testimonial_text' => 'Great experience!',
            ],
        ]);

        $response->assertRedirect('/c/' . $space->public_slug . '?submitted=1');

        $this->assertDatabaseHas('testimonials', [
            'space_id' => $space->id,
            'submitter_email' => 'jane@example.com',
            'consent_state' => 'granted',
        ]);

        $this->get('/c/' . $space->public_slug . '?submitted=1')
            ->assertOk()
            ->assertSee("You're officially awesome!");
    }

    public function test_submission_without_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $space = Space::factory()->for($user)->create();

        $this->post('/c/' . $space->public_slug, [
            'public_token' => 'wrong-token',
            'submitter_name' => 'A',
            'submitter_email' => 'a@example.com',
            'testimonial_text' => 'Text',
            'consent_state' => 'granted',
        ])->assertStatus(403);
    }

    public function test_free_plan_enforces_100_testimonial_limit(): void
    {
        $user = User::factory()->create();
        $space = Space::factory()->for($user)->create();

        // Pre-fill 100 testimonials.
        Testimonial::factory()->count(100)->for($space)->create();

        $response = $this->post('/c/' . $space->public_slug, [
            'public_token' => $space->public_token,
            'submitter_name' => 'One more',
            'submitter_email' => 'one@example.com',
            'testimonial_text' => 'Text',
            'consent_state' => 'granted',
        ]);

        $response->assertSessionHasErrors('limit');
        $this->assertDatabaseMissing('testimonials', [
            'submitter_email' => 'one@example.com',
        ]);
    }

    public function test_honeypot_blocks_bots(): void
    {
        $user = User::factory()->create();
        $space = Space::factory()->for($user)->create();

        $response = $this->post('/c/' . $space->public_slug, [
            'public_token' => $space->public_token,
            'submitter_name' => 'Bot',
            'submitter_email' => 'bot@example.com',
            'testimonial_text' => 'spam',
            'consent_state' => 'granted',
            'website' => 'http://spam.example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('testimonials', [
            'submitter_email' => 'bot@example.com',
        ]);
    }

    public function test_submission_with_declined_consent_still_persists_but_is_not_publishable(): void
    {
        $user = User::factory()->create();
        $space = Space::factory()->for($user)->create();

        $this->post('/c/' . $space->public_slug, [
            'public_token' => $space->public_token,
            'submitter_name' => 'Quiet',
            'submitter_email' => 'quiet@example.com',
            'testimonial_text' => 'Not for public',
            'consent_state' => 'declined',
        ])->assertRedirect();

        $t = Testimonial::where('submitter_email', 'quiet@example.com')->first();
        $this->assertNotNull($t);
        $this->assertSame('declined', $t->consent_state);
    }
}
