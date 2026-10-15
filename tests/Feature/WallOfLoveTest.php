<?php

namespace Tests\Feature;

use App\Models\EmbedConfig;
use App\Models\Space;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD §8: Embed widget builder + Wall of Love public endpoint.
 */
class WallOfLoveTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_embed_only_renders_approved_testimonials(): void
    {
        $user = User::factory()->create();
        $space = Space::factory()->for($user)->create();
        $embed = EmbedConfig::factory()->for($space)->create();

        // Approved
        Testimonial::factory()->for($space)->onWallOfLove()->create(['submitter_name' => 'PublicOne']);
        // Not on wall of love
        Testimonial::factory()->for($space)->grantedConsent()->create(['submitter_name' => 'ShouldNotShowOne']);
        // Declined consent
        Testimonial::factory()->for($space)->declinedConsent()->create([
            'submitter_name' => 'ShouldNotShowTwo',
            'is_wall_of_love' => true,
        ]);
        // Hidden
        Testimonial::factory()->for($space)->onWallOfLove()->create([
            'submitter_name' => 'ShouldNotShowThree',
            'is_hidden' => true,
        ]);

        $response = $this->get('/embed/' . $embed->public_id);

        $response->assertOk();
        $response->assertSee('PublicOne');
        $response->assertDontSee('ShouldNotShowOne');
        $response->assertDontSee('ShouldNotShowTwo');
        $response->assertDontSee('ShouldNotShowThree');
    }

    public function test_dashboard_embed_index_renders_snippet(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $space = Space::factory()->for($user)->create();
        $embed = EmbedConfig::factory()->for($space)->create();

        $response = $this->get(route('dashboard.embeds.index', $space));

        $response->assertOk();
        $response->assertSee('iframe', false);
        $response->assertSee(url('/embed/' . $embed->public_id));
    }

    public function test_dashboard_embed_create_form_has_layout_theme_color_animation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $space = Space::factory()->for($user)->create();

        $this->get(route('dashboard.embeds.create', $space))
            ->assertOk()
            ->assertSee('Layout')
            ->assertSee('Theme')
            ->assertSee('Background color')
            ->assertSee('animations')
            ->assertSee('Live preview');
    }

    public function test_dashboard_embed_create_persists_config(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $space = Space::factory()->for($user)->create();

        $this->post(route('dashboard.embeds.store', $space), [
            'layout' => 'carousel',
            'theme_mode' => 'dark',
            'background_color' => '#222222',
            'animation_enabled' => 1,
        ])->assertRedirect(route('dashboard.embeds.index', $space));

        $this->assertDatabaseHas('embed_configs', [
            'space_id' => $space->id,
            'layout' => 'carousel',
            'theme_mode' => 'dark',
            'background_color' => '#222222',
        ]);
    }

    public function test_dashboard_sidebar_has_wall_of_love_link(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Space::factory()->for($user)->create();

        $this->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Wall of Love');
    }
}