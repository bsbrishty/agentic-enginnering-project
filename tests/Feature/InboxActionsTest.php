<?php

namespace Tests\Feature;

use App\Models\Space;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD §7: Inbox listing, filters, and testimonial actions
 * (favorite / wall_of_love / hide / delete) with consent gating.
 */
class InboxActionsTest extends TestCase
{
    use RefreshDatabase;

    private function actingOwner(): array
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        $space = Space::factory()->for($owner)->create();
        return [$owner, $space];
    }

    public function test_inbox_lists_testimonials_for_owned_spaces(): void
    {
        [$owner, $space] = $this->actingOwner();
        Testimonial::factory()->for($space)->count(3)->create();

        $this->get('/dashboard/inbox')
            ->assertOk()
            ->assertSee('Testimonial inbox');
    }

    public function test_inbox_filters_by_space_consent_status_and_search(): void
    {
        [$owner, $space] = $this->actingOwner();
        Testimonial::factory()->for($space)->grantedConsent()->create([
            'submitter_name' => 'Alice',
        ]);
        Testimonial::factory()->for($space)->declinedConsent()->create([
            'submitter_name' => 'Bob',
        ]);

        $this->get('/dashboard/inbox?consent_state=granted')
            ->assertOk()
            ->assertSee('Alice')
            ->assertDontSee('Bob');

        $this->get('/dashboard/inbox?consent_state=declined')
            ->assertOk()
            ->assertSee('Bob')
            ->assertDontSee('Alice');

        $this->get('/dashboard/inbox?status=hidden')
            ->assertOk();
    }

    public function test_favorite_toggle_works(): void
    {
        [$owner, $space] = $this->actingOwner();
        $t = Testimonial::factory()->for($space)->create(['is_favorite' => false]);

        $this->patch(route('dashboard.testimonials.update', $t), [
            'action' => 'favorite',
        ])->assertRedirect();

        $this->assertTrue($t->fresh()->is_favorite);
    }

    public function test_wall_of_love_toggle_blocked_without_consent(): void
    {
        [$owner, $space] = $this->actingOwner();
        $t = Testimonial::factory()->for($space)->declinedConsent()->create([
            'is_wall_of_love' => false,
        ]);

        $this->patch(route('dashboard.testimonials.update', $t), [
            'action' => 'wall_of_love',
        ])->assertRedirect();

        $this->assertFalse($t->fresh()->is_wall_of_love);
    }

    public function test_wall_of_love_toggle_allowed_with_consent(): void
    {
        [$owner, $space] = $this->actingOwner();
        $t = Testimonial::factory()->for($space)->grantedConsent()->create([
            'is_wall_of_love' => false,
        ]);

        $this->patch(route('dashboard.testimonials.update', $t), [
            'action' => 'wall_of_love',
        ])->assertRedirect();

        $this->assertTrue($t->fresh()->is_wall_of_love);
    }

    public function test_hide_unhide_toggle(): void
    {
        [$owner, $space] = $this->actingOwner();
        $t = Testimonial::factory()->for($space)->create(['is_hidden' => false]);

        $this->patch(route('dashboard.testimonials.update', $t), [
            'action' => 'hidden',
        ])->assertRedirect();

        $this->assertTrue($t->fresh()->is_hidden);

        $this->patch(route('dashboard.testimonials.update', $t), [
            'action' => 'hidden',
        ])->assertRedirect();

        $this->assertFalse($t->fresh()->is_hidden);
    }

    public function test_delete_testimonial(): void
    {
        [$owner, $space] = $this->actingOwner();
        $t = Testimonial::factory()->for($space)->create();

        $this->delete(route('dashboard.testimonials.destroy', $t))
            ->assertRedirect();

        $this->assertDatabaseMissing('testimonials', ['id' => $t->id]);
    }

    public function test_inbox_only_shows_testimonials_from_owned_spaces(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($owner);

        $mySpace = Space::factory()->for($owner)->create();
        $theirSpace = Space::factory()->for($other)->create();

        Testimonial::factory()->for($mySpace)->create([
            'submitter_name' => 'Mine',
        ]);
        Testimonial::factory()->for($theirSpace)->create([
            'submitter_name' => 'Theirs',
        ]);

        $this->get('/dashboard/inbox')
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('Theirs');
    }
}