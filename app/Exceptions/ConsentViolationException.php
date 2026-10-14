<?php

namespace App\Exceptions;

use App\Models\Testimonial;
use RuntimeException;

/**
 * Thrown when an attempt is made to publish a testimonial that has not
 * (or has explicitly refused to) consent to public sharing.
 *
 * Per docs/data-model.md §6 and PRD §7, an owner cannot override a
 * customer's explicit refusal of public sharing.
 */
class ConsentViolationException extends RuntimeException
{
    public function __construct(
        public readonly Testimonial $testimonial,
        string $message = 'This testimonial does not have consent to be published.',
    ) {
        parent::__construct($message);
    }
}
