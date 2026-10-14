<?php

namespace App\Services;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Encapsulates the dashboard inbox query.
 *
 * Scopes to spaces owned by the user, applies optional filters, supports
 * a long-form `q` against testimonial_text (via FULLTEXT) and a prefix
 * `q` against submitter_name/email (via B-tree).
 */
class InboxService
{
    private const PER_PAGE = 25;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(User $user, array $filters): LengthAwarePaginator
    {
        $query = Testimonial::query()
            ->whereHas('space', function (Builder $q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orderByDesc('created_at');

        $this->applyFilters($query, $filters);

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['space_id'])) {
            $query->where('space_id', $filters['space_id']);
        }

        if (isset($filters['rating']) && $filters['rating'] !== '') {
            $query->where('rating', (int) $filters['rating']);
        }

        if (! empty($filters['consent_state'])) {
            $query->where('consent_state', $filters['consent_state']);
        }

        foreach (['is_favorite', 'is_wall_of_love', 'is_hidden'] as $flag) {
            if (isset($filters[$flag]) && $filters[$flag] !== '') {
                $query->where($flag, (bool) $filters[$flag]);
            }
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        if (! empty($filters['q'])) {
            $term = (string) $filters['q'];
            $query->where(function (Builder $q) use ($term) {
                // FULLTEXT on long-form text; prefix LIKE on name/email.
                $q->whereFullText('testimonial_text', $term)
                    ->orWhere('submitter_name', 'like', $term . '%')
                    ->orWhere('submitter_email', 'like', $term . '%');
            });
        }
    }
}
