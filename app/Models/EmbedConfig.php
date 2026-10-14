<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id                            UUID v4, used in /embed/{public_id} URL
 * @property int $space_id
 * @property string $layout                                masonry|carousel
 * @property string $theme_mode                            light|dark
 * @property string $background_color                      #RRGGBB
 * @property bool $animation_enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read Space $space
 */
class EmbedConfig extends Model
{
    /** @use HasFactory<\Database\Factories\EmbedConfigFactory> */
    use HasFactory;

    protected $table = 'embed_configs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'space_id',
        'layout',
        'theme_mode',
        'background_color',
        'animation_enabled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'animation_enabled' => 'boolean',
        ];
    }

    /**
     * The space this embed configuration belongs to.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Auto-generate a UUID v4 `public_id` on create.
     *
     * Only fills the field if it is currently empty, so explicit values
     * passed to `EmbedConfig::create([...])` are preserved.
     */
    protected static function booted(): void
    {
        static::creating(function (EmbedConfig $config) {
            if (empty($config->public_id)) {
                $config->public_id = (string) Str::uuid();
            }
        });
    }
}
