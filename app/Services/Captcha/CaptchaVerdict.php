<?php

namespace App\Services\Captcha;

/**
 * The manager's final decision for one submission: whether to allow it, the
 * message to show if not, and whether it was only allowed because a provider
 * outage triggered a fail-open (which the caller records in the audit log).
 */
class CaptchaVerdict
{
    public function __construct(
        public readonly bool $allowed,
        public readonly string $message = '',
        public readonly bool $failedOpen = false,
        public readonly string $layer = '',
    ) {
    }

    public static function allow(bool $failedOpen = false, string $layer = ''): self
    {
        return new self(true, '', $failedOpen, $layer);
    }

    public static function deny(string $message, string $layer = ''): self
    {
        return new self(false, $message, false, $layer);
    }
}
