<?php

namespace App\Services;

use App\Models\Testimonial;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates "testimonials over time" data for the dashboard chart (PRD §4).
 *
 * The dashboard chart is a per-day count of testimonials created on the
 * owner's spaces. This service:
 *   - Filters by `User` (only spaces owned by that user are counted).
 *   - Restricts to a date range: `7d` (last 7 days), `30d` (last 30 days,
 *     the default), or `all` (no lower bound, capped at year 2000 so the
 *     query stays cheap on large datasets).
 *   - Zero-fills missing dates so the chart renders a continuous timeline
 *     with no gaps. The Chart.js renderer depends on labels[i] and data[i]
 *     having parallel indices.
 *
 * Cross-driver: date truncation uses `strftime` (SQLite) or `DATE_FORMAT`
 * (MySQL) based on the active connection. Tests run on `:memory:` SQLite.
 */
class DashboardAnalyticsService
{
    /** Cap the "all time" lower bound so the timeline doesn't span decades. */
    private const ALL_TIME_FLOOR_YEAR = 2000;

    /**
     * Per-day testimonial counts for $user's spaces, zero-filled across the
     * range.
     *
     * @param  string  $range  One of '7d', '30d', 'all'. Unknown values fall
     *                         back to '30d' so the dashboard never crashes on
     *                         a bad query string.
     * @return array{labels: array<int,string>, data: array<int,int>, start: string, end: string, range: string}
     */
    public function testimonialsPerDay(User $user, string $range = '30d'): array
    {
        if (! in_array($range, ['7d', '30d', 'all'], true)) {
            $range = '30d';
        }

        [$start, $end] = $this->resolveRange($range);

        $dayExpr = $this->dayExpression();

        // The selectRaw group-by truncates created_at to a date string. We
        // pluck to ['2026-09-15' => 3, ...] for O(1) lookup in the
        // zero-fill loop below.
        $counts = Testimonial::query()
            ->whereHas('space', fn ($q) => $q->where('user_id', $user->id))
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->selectRaw("$dayExpr as day, COUNT(*) as c")
            ->groupBy('day')
            ->pluck('c', 'day')
            ->all();

        $labels = [];
        $data = [];
        $cursor = $start->startOfDay();
        $endDay = $end->startOfDay();

        // Walk day-by-day so the chart is a true timeline with no gaps.
        // Carbon's addDay() handles month/year boundaries correctly.
        while ($cursor->lessThanOrEqualTo($endDay)) {
            $key = $cursor->format('Y-m-d');
            $labels[] = $key;
            $data[] = (int) ($counts[$key] ?? 0);
            $cursor = $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'range' => $range,
        ];
    }

    /**
     * SQL expression that truncates a timestamp to a `YYYY-MM-DD` string.
     *
     * @return string
     */
    private function dayExpression(): string
    {
        return DB::getDriverName() === 'mysql'
            ? "DATE_FORMAT(created_at, '%Y-%m-%d')"
            : "strftime('%Y-%m-%d', created_at)";
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolveRange(string $range): array
    {
        $today = CarbonImmutable::today();

        return match ($range) {
            // 7-day window is 6 days back through today (7 inclusive).
            '7d' => [$today->subDays(6), $today],
            // 30-day window is 29 days back through today (30 inclusive).
            '30d' => [$today->subDays(29), $today],
            // "All time" uses a fixed lower bound to keep the timeline finite.
            'all' => [CarbonImmutable::create(self::ALL_TIME_FLOOR_YEAR, 1, 1), $today],
            // Caller already validated, but be defensive.
            default => [$today->subDays(29), $today],
        };
    }
}
