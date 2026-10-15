<?php

namespace Database\Factories;

use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Space>
 */
class SpaceFactory extends Factory
{
    protected $model = Space::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->company() . ' Testimonials',
            'subtitle' => fake()->catchPhrase(),
            'collection_question' => 'What did you like most about working with us?',
            'theme' => 'minimal_light',
            'rating_enabled' => true,
            'star_ratings_enabled' => true,
            'is_active' => true,
            'fields_config' => Space::defaultFieldsConfig(),
            'public_slug' => Str::slug(fake()->company()) . '-' . Str::random(6),
            'public_token' => Str::random(64),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
