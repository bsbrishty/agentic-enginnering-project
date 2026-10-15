<?php

namespace Tests\Feature;

use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the Space model cast / default config behavior (PRD §5).
 */
class SpaceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_fields_config_is_cast_to_array(): void
    {
        $space = Space::factory()->for(User::factory())->create([
            'fields_config' => [
                ['key' => 'name', 'enabled' => true, 'required' => true],
            ],
        ]);

        $this->assertIsArray($space->fields_config);
        $this->assertCount(1, $space->fields_config);
        $this->assertSame('name', $space->fields_config[0]['key']);
    }

    public function test_fields_config_resolved_returns_default_when_empty(): void
    {
        $space = Space::factory()->for(User::factory())->create([
            'fields_config' => [],
        ]);

        $resolved = $space->fields_config_resolved;
        $this->assertNotEmpty($resolved);
        $this->assertSame('name', $resolved[0]['key']);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $space = Space::factory()->for(User::factory())->inactive()->create();
        $this->assertFalse($space->is_active);
    }

    public function test_star_ratings_enabled_falls_back_to_legacy_rating_enabled(): void
    {
        $space = Space::factory()->for(User::factory())->create([
            'rating_enabled' => true,
            'star_ratings_enabled' => false,
        ]);

        $this->assertTrue($space->star_ratings_enabled);
    }

    public function test_default_fields_config_includes_expected_keys(): void
    {
        $defaults = Space::defaultFieldsConfig();
        $keys = array_column($defaults, 'key');
        $this->assertContains('name', $keys);
        $this->assertContains('email', $keys);
        $this->assertContains('testimonial_text', $keys);
    }
}