<?php

namespace App\Services\Captcha\Providers;

class HcaptchaProvider extends AbstractHttpProvider
{
    public function key(): string
    {
        return 'hcaptcha';
    }

    public function label(): string
    {
        return 'hCaptcha';
    }

    public function description(): string
    {
        return 'A privacy-forward checkbox challenge and a drop-in alternative to reCAPTCHA.';
    }

    protected function verifyUrl(): string
    {
        return 'https://api.hcaptcha.com/siteverify';
    }

    protected function scriptUrl(): string
    {
        return 'https://js.hcaptcha.com/1/api.js';
    }

    protected function responseField(): string
    {
        return 'h-captcha-response';
    }

    protected function widgetClass(): string
    {
        return 'h-captcha';
    }

    protected function siteKeySetting(): string
    {
        return 'captcha_hcaptcha_site';
    }

    protected function secretKeySetting(): string
    {
        return 'captcha_hcaptcha_secret';
    }
}
