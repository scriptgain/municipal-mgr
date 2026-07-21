<?php

namespace App\View\Components;

use App\Services\Captcha\CaptchaManager;
use App\Services\Captcha\HoneypotGuard;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Drops spam protection into a form. Usage: <x-captcha context="report" />.
 *
 * Always renders the baseline honeypot + time-trap. Renders the selected
 * provider's widget and vendor script only where that provider is active AND
 * the per-context toggle is on, so a vendor script is never emitted for a form
 * that is not using it, and a form with the provider switched off still carries
 * the baseline. All decisions are made here; the Blade file is markup only.
 */
class Captcha extends Component
{
    public string $baselineFields;
    public bool $providerActive;
    public string $widget = '';
    /** @var array<int,string> */
    public array $scripts = [];
    public bool $needsHelperJs = false;

    public function __construct(CaptchaManager $manager, public string $context = 'forms')
    {
        $this->baselineFields = HoneypotGuard::fields();
        $this->providerActive = $manager->providerActiveFor($this->context);

        if ($this->providerActive) {
            $provider = $manager->active();
            $this->widget = $provider->widget();
            $this->scripts = $provider->scripts();
            // Only reCAPTCHA v3 needs our helper to fetch its invisible token.
            $this->needsHelperJs = $provider->key() === 'recaptcha_v3';
        }
    }

    public function render(): View
    {
        return view('components.captcha');
    }
}
