<?php

namespace Database\Factories;

use App\Models\EmbedConfig;
use App\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmbedConfig>
 */
class EmbedConfigFactory extends Factory
{
    protected $model = EmbedConfig::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'space_id' => Space::factory(),
            'layout' => 'masonry',
            'theme_mode' => 'light',
            'background_color' => '#ffffff',
            'animation_enabled' => true,
        ];
    }
}