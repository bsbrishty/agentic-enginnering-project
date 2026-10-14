<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $subtitle
 * @property string $collection_question
 * @property string $theme                              minimal_light|modern_dark|warm_friendly
 * @property bool $rating_enabled
 * @property array $fields_config                       Array of {key, type, enabled, required}
 * @property string $public_slug
 * @property string $public_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read User $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Testimonial> $testimonials
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EmbedConfig> $embedConfigs
 */
class Space extends Model
{
    /** @use HasFactory<\Database\Factories\SpaceFactory> */
    use HasFactory;

    protected $table = 'spaces';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'subtitle',
        'collection_question',
        'theme',
        'rating_enabled',
        'fields_config',
        'public_slug',
        'public_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating_enabled' => 'boolean',
            'fields_config' => 'array',
        ];
    }

    /**
     * The user who owns this space. Method named for clarity; FK column is `user_id`.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Testimonials submitted to this space.
     */
    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    /**
     * Embed widget configurations for this space.
     */
    public function embedConfigs(): HasMany
    {
        return $this->hasMany(EmbedConfig::class);
    }

    /**
     * Auto-generate `public_slug` and `public_token` on create.
     *
     * Only fills the field if it is currently empty, so explicit values
     * passed to `Space::create([...])` are preserved.
     */
    protected static function booted(): void
    {
        static::creating(function (Space $space) {
            if (empty($space->public_slug)) {
                $base = $space->title !== null && $space->title !== ''
                    ? Str::slug($space->title)
                    : 'space';
                $space->public_slug = $base . '-' . Str::random(6);
            }

            if (empty($space->public_token)) {
                $space->public_token = Str::random(64);
            }
        });
    }
}
