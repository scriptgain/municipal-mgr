<?php

namespace App\Services\Captcha\Providers;

use App\Services\Captcha\CaptchaProvider;
use App\Services\Captcha\CaptchaResult;
use Illuminate\Http\Request;

/**
 * "None". No provider challenge at all — but the always-on honeypot and
 * time-trap still run underneath (they are applied by the manager, not here),
 * so selecting None still leaves the baseline in place.
 */
class NullProvider implements CaptchaProvider
{
    public function key(): string
    {
        return 'none';
    }

    public function label(): string
    {
        return 'None (Baseline Only)';
    }

    public function description(): string
    {
        return 'No challenge. The honeypot and time-trap still run, so forms are never fully unprotected.';
    }

    public function isThirdParty(): bool
    {
        return false;
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function scripts(): array
    {
        return [];
    }

    public function widget(): string
    {
        return '';
    }

    public function verify(Request $request): CaptchaResult
    {
        return CaptchaResult::pass();
    }

    public function selfTest(): CaptchaResult
    {
        return CaptchaResult::pass('No provider is active. The baseline honeypot and time-trap are still enforced.');
    }
}
