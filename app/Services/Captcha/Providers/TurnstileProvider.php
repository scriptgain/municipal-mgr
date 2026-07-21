<?php

namespace App\Services\Captcha\Providers;

class TurnstileProvider extends AbstractHttpProvider
{
    public function key(): string
    {
        return 'turnstile';
    }

    public function label(): string
    {
        return 'Cloudflare Turnstile';
    }

    public function description(): string
    {
        return 'A privacy-forward challenge with no puzzle. A natural fit for sites already behind Cloudflare.';
    }

    protected function verifyUrl(): string
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    }

    protected function scriptUrl(): string
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    }

    protected function responseField(): string
    {
        return 'cf-turnstile-response';
    }

    protected function widgetClass(): string
    {
        return 'cf-turnstile';
    }

    protected function siteKeySetting(): string
    {
        return 'captcha_turnstile_site';
    }

    protected function secretKeySetting(): string
    {
        return 'captcha_turnstile_secret';
    }
}
