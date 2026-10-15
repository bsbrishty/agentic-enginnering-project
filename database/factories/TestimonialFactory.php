<?php

namespace Database\Factories;

use App\Models\Space;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Testimonial>
 */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();
        $email = fake()->unique()->safeEmail();
        $text = fake()->paragraph(3);

        return [
            'space_id' => Space::factory(),
            'submitter_name' => $name,
            'submitter_email' => $email,
            'submitter_company' => fake()->company(),
            'testimonial_text' => $text,
            'rating' => fake()->numberBetween(3, 5),
            // The `field_values` JSON column is NOT NULL; provide a
            // populated default so factories work in SQLite/MySQL alike.
            'field_values' => [
                'name' => $name,
                'email' => $email,
                'testimonial_text' => $text,
            ],
            'consent_state' => 'granted',
            'is_favorite' => false,
            'is_wall_of_love' => false,
            'is_hidden' => false,
            'submitted_at' => now(),
        ];
    }

    public function grantedConsent(): static
    {
        return $this->state(fn () => ['consent_state' => 'granted']);
    }

    public function declinedConsent(): static
    {
        return $this->state(fn () => ['consent_state' => 'declined']);
    }

    public function onWallOfLove(): static
    {
        return $this->state(fn () => [
            'consent_state' => 'granted',
            'is_wall_of_love' => true,
        ]);
    }
}
