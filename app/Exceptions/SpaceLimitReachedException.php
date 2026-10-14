<?php

namespace App\Exceptions;

use App\Models\Space;
use App\Models\User;
use RuntimeException;

/**
 * Thrown when an owner tries to create a space beyond their plan's limit.
 *
 * Per docs/data-model.md §7.2:
 *   - Free: 3 spaces max
 *   - Pro: 25 spaces max
 */
class SpaceLimitReachedException extends RuntimeException
{
    public function __construct(
        public readonly User $user,
        public readonly int $current,
        public readonly int $max,
    ) {
        parent::__construct(sprintf(
            'Space limit reached (%d / %d). Upgrade to Pro to create more spaces.',
            $current,
            $max,
        ));
    }
}
