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
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Testimonial> $testimonials
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EmbedConfig> $embedConfigs
 */
class Space extends Model
{
    /** @use HasFactory<\Database\Factories\SpaceFactory> */
    use HasFactory;

    protected $table = 'spaces';

    /**
     * Route model binding uses `public_slug` (the human-readable URL
     * component). The slug is guessable; the controller additionally
     * verifies the submitted `public_token` for write actions.
     */
    public function getRouteKeyName(): string
    {
        return 'public_slug';
    }

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
        'star_ratings_enabled',
        'is_active',
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
            'star_ratings_enabled' => 'boolean',
            'is_active' => 'boolean',
            'fields_config' => 'array',
        ];
    }

    /**
     * Resolve the effective star-ratings flag.
     *
     * The PRD column is `star_ratings_enabled`; the legacy column
     * `rating_enabled` is kept for backward compatibility and read as a
     * fallback so existing rows / queries continue to work.
     *
     * Resolution rules (most-specific first):
     *   1. If the legacy `rating_enabled` is explicitly true, return true.
     *      This preserves backward compatibility for older rows where the
     *      legacy column was the only source of truth.
     *   2. Otherwise, return the value of the new `star_ratings_enabled`
     *      column (defaulting to false if absent or null).
     */
    public function getStarRatingsEnabledAttribute(): bool
    {
        if (array_key_exists('rating_enabled', $this->attributes)
            && $this->attributes['rating_enabled'] === true) {
            return true;
        }

        if (array_key_exists('star_ratings_enabled', $this->attributes)
            && $this->attributes['star_ratings_enabled'] !== null) {
            return (bool) $this->attributes['star_ratings_enabled'];
        }

        return false;
    }

    /**
     * The default `fields_config` array. Used when the column is empty
     * (older rows, factory-built spaces, or the public form which cannot
     * itself author the JSON).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultFieldsConfig(): array
    {
        return [
            ['key' => 'name',             'type' => 'text',     'enabled' => true,  'required' => true],
            ['key' => 'email',            'type' => 'email',    'enabled' => true,  'required' => true],
            ['key' => 'testimonial_text', 'type' => 'textarea', 'enabled' => true,  'required' => true],
            ['key' => 'company',          'type' => 'text',     'enabled' => false, 'required' => false],
            ['key' => 'social_links',     'type' => 'url',      'enabled' => false, 'required' => false],
            ['key' => 'profile_photo',    'type' => 'file',     'enabled' => false, 'required' => false],
        ];
    }

    /**
     * Always returns a non-empty fields_config array.
     *
     * Older rows may have NULL or `[]`; downstream rendering code (the
     * public form) requires a usable list, so this accessor fills in
     * the default when missing.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFieldsConfigResolvedAttribute(): array
    {
        $raw = $this->fields_config;

        if (is_array($raw) && $raw !== []) {
            return $raw;
        }

        return self::defaultFieldsConfig();
    }

    /**
     * The user who owns this space. Method named for clarity; FK column is `user_id`.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The standard `user` relationship. Same target as `owner()`, but
     * the conventional name makes Eloquent's `$space->user` work in
     * templates, scopes, and any code that uses the natural FK name.
     */
    public function user(): BelongsTo
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
