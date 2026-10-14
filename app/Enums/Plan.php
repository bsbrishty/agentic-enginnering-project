<?php

namespace App\Enums;

/**
 * The two subscription plans.
 *
 * Limit constants come from docs/data-model.md §7.2 and the PRD §9 table.
 * The DB stores the tier as `('free','pro')` ENUM; this enum is the
 * application-side mirror.
 */
enum Plan: string
{
    case Free = 'free';
    case Pro = 'pro';

    /** Max number of spaces a user on this plan can own. */
    public function maxSpaces(): int
    {
        return match ($this) {
            self::Free => 3,
            self::Pro => 25,
        };
    }

    /** Max number of testimonials allowed in any single space. */
    public function maxTestimonialsPerSpace(): int
    {
        return match ($this) {
            self::Free => 100,
            self::Pro => 1000,
        };
    }
}
