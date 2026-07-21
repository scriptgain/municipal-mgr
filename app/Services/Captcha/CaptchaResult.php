<?php

namespace App\Services\Captcha;

/**
 * The outcome of a single provider (or guard) check.
 *
 * Two booleans, kept deliberately separate:
 *  - passed:    did the visitor clear the challenge?
 *  - reachable: could we actually reach the service to ask?
 *
 * The distinction is the whole point of the configurable fail policy. A wrong
 * answer (passed=false, reachable=true) is always a rejection. A provider
 * outage (passed=false, reachable=false) is where login and a pothole report
 * are allowed to diverge: login fails closed, the pothole report may fail open.
 */
class CaptchaResult
{
    public function __construct(
        public readonly bool $passed,
        public readonly bool $reachable = true,
        public readonly string $message = '',
        public readonly ?float $score = null,
    ) {
    }

    public static function pass(string $message = '', ?float $score = null): self
    {
        return new self(true, true, $message, $score);
    }

    /** The visitor failed a reachable challenge: a real rejection. */
    public static function fail(string $message = 'Verification failed. Please try again.', ?float $score = null): self
    {
        return new self(false, true, $message, $score);
    }

    /** We could not reach the service: the fail policy decides what happens. */
    public static function unreachable(string $message = 'The verification service could not be reached.'): self
    {
        return new self(false, false, $message);
    }
}
