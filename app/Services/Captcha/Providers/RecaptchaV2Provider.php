<?php

namespace App\Services\Captcha\Providers;

class RecaptchaV2Provider extends AbstractHttpProvider
{
    public function key(): string
    {
        return 'recaptcha_v2';
    }

    public function label(): string
    {
        return 'Google reCAPTCHA v2';
    }

    public function description(): string
    {
        return 'The familiar "I\'m Not A Robot" checkbox. Widely recognised, but sends visitor data to Google.';
    }

    protected function verifyUrl(): string
    {
        return 'https://www.google.com/recaptcha/api/siteverify';
    }

    protected function scriptUrl(): string
    {
        return 'https://www.google.com/recaptcha/api.js';
    }

    protected function responseField(): string
    {
        return 'g-recaptcha-response';
    }

    protected function widgetClass(): string
    {
        return 'g-recaptcha';
    }

    protected function siteKeySetting(): string
    {
        return 'captcha_recaptcha_v2_site';
    }

    protected function secretKeySetting(): string
    {
        return 'captcha_recaptcha_v2_secret';
    }
}
