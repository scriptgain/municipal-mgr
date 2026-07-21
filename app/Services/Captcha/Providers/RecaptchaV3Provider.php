<?php

namespace App\Services\Captcha\Providers;

use App\Services\Captcha\CaptchaResult;
use App\Services\Captcha\CaptchaSettings;
use Illuminate\Http\Request;

/**
 * reCAPTCHA v3: invisible and score-based. There is no checkbox; a token is
 * fetched by JavaScript and scored 0.0–1.0 server-side. The admin-set threshold
 * decides the pass mark.
 *
 * Note: v3 has no official always-pass public test key, so it ships with blank
 * keys and must be configured before it can be selected in earnest.
 */
class RecaptchaV3Provider extends AbstractHttpProvider
{
    public function key(): string
    {
        return 'recaptcha_v3';
    }

    public function label(): string
    {
        return 'Google reCAPTCHA v3';
    }

    public function description(): string
    {
        return 'Invisible and score-based, with no puzzle for the visitor. Needs a tuned score threshold and sends data to Google.';
    }

    protected function verifyUrl(): string
    {
        return 'https://www.google.com/recaptcha/api/siteverify';
    }

    protected function scriptUrl(): string
    {
        // The render parameter loads the site key so grecaptcha.execute() works.
        return 'https://www.google.com/recaptcha/api.js?render=' . urlencode($this->siteKey());
    }

    protected function responseField(): string
    {
        return 'g-recaptcha-response';
    }

    protected function widgetClass(): string
    {
        return 'g-recaptcha-v3';
    }

    protected function siteKeySetting(): string
    {
        return 'captcha_recaptcha_v3_site';
    }

    protected function secretKeySetting(): string
    {
        return 'captcha_recaptcha_v3_secret';
    }

    public function widget(): string
    {
        if (! $this->isConfigured()) {
            return '<p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200">reCAPTCHA v3 is not configured. Add the site and secret keys in Settings. There is no public test key for v3.</p>';
        }

        $site = e($this->siteKey());

        // captcha.js hooks the form submit, calls grecaptcha.execute() and drops
        // the token into the hidden input. The <noscript> is honest about v3's
        // hard requirement on JavaScript.
        return <<<HTML
            <div data-recaptcha-v3 data-sitekey="{$site}" data-action="submit" hidden></div>
            <input type="hidden" name="g-recaptcha-response" value="">
            <noscript>
                <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200">This form uses invisible verification that needs JavaScript. Please enable JavaScript, or contact us another way.</p>
            </noscript>
            HTML;
    }

    public function verify(Request $request): CaptchaResult
    {
        $result = parent::verify($request);

        // Only apply the score gate once the token itself verified.
        if ($result->passed && $result->score !== null) {
            $threshold = CaptchaSettings::v3Threshold();
            if ($result->score < $threshold) {
                return CaptchaResult::fail(
                    'Your submission looked automated (score ' . $result->score . ').',
                    $result->score
                );
            }
        }

        return $result;
    }
}
