<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $space_id
 * @property string $submitter_name
 * @property string $submitter_email
 * @property string|null $submitter_company
 * @property array<string>|null $submitter_social_links
 * @property string $testimonial_text
 * @property int|null $rating                              1-5 when spaces.rating_enabled
 * @property string|null $profile_photo_url
 * @property array $field_values
 * @property string $consent_state                         granted|declined|not_asked
 * @property bool $is_favorite
 * @property bool $is_wall_of_love
 * @property bool $is_hidden
 * @property \Illuminate\Support\Carbon $submitted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * Note: `submitter_email_normalized` is a STORED generated column in MySQL and
 * is intentionally NOT declared on this model. Eloquent will not see it unless
 * explicitly queried via raw SQL or added to $appends.
 *
 * @property-read Space $space
 */
class Testimonial extends Model
{
    /** @use HasFactory<\Database\Factories\TestimonialFactory> */
    use HasFactory;

    protected $table = 'testimonials';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'space_id',
        'submitter_name',
        'submitter_email',
        'submitter_company',
        'submitter_social_links',
        'testimonial_text',
        'rating',
        'profile_photo_url',
        'field_values',
        'consent_state',
        'is_favorite',
        'is_wall_of_love',
        'is_hidden',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
            'is_wall_of_love' => 'boolean',
            'is_hidden' => 'boolean',
            'submitter_social_links' => 'array',
            'field_values' => 'array',
            'rating' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * The space this testimonial was submitted to.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }
}
