<?php

namespace App\Services\Captcha;

use App\Models\FormDefinition;
use App\Services\Captcha\Providers\BuiltinChallengeProvider;
use App\Services\Captcha\Providers\HcaptchaProvider;
use App\Services\Captcha\Providers\NullProvider;
use App\Services\Captcha\Providers\RecaptchaV2Provider;
use App\Services\Captcha\Providers\RecaptchaV3Provider;
use App\Services\Captcha\Providers\TurnstileProvider;
use Illuminate\Http\Request;

/**
 * The registry and orchestrator for spam protection.
 *
 * A new provider is a class plus ONE line in registerDefaults(): the settings
 * screen, the <x-captcha> component and the middleware all read providers back
 * out of this one registry, so nothing else needs to change.
 *
 * On every protected submission it runs the always-on baseline first, then the
 * selected provider (only where the per-form toggle is on), and applies the
 * configurable fail policy to a provider outage.
 */
class CaptchaManager
{
    /** The public entry points that carry their own on/off toggle. */
    public const CONTEXTS = ['login', 'report', 'contact', 'forms'];

    /** @var array<string, CaptchaProvider> */
    protected array $providers = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    protected function registerDefaults(): void
    {
        // Order here is the order shown in the settings dropdown.
        $this->register(new NullProvider);
        $this->register(new BuiltinChallengeProvider);
        $this->register(new RecaptchaV2Provider);
        $this->register(new RecaptchaV3Provider);
        $this->register(new HcaptchaProvider);
        $this->register(new TurnstileProvider);
    }

    public function register(CaptchaProvider $provider): void
    {
        $this->providers[$provider->key()] = $provider;
    }

    /** @return array<string, CaptchaProvider> */
    public function providers(): array
    {
        return $this->providers;
    }

    public function provider(string $key): CaptchaProvider
    {
        return $this->providers[$key] ?? $this->providers['none'];
    }

    /** The provider the admin has selected (defaults to the built-in challenge). */
    public function active(): CaptchaProvider
    {
        return $this->provider(CaptchaSettings::activeProviderKey());
    }

    /**
     * Should the provider challenge (not the baseline) appear/run for a context?
     * True only when a real provider is active AND the per-form toggle is on.
     */
    public function providerActiveFor(string $context): bool
    {
        $active = $this->active();

        return $active->key() !== 'none' && CaptchaSettings::enabledFor($context);
    }

    /** Map a form-builder form to its context, so "Contact Us" gets its own toggle. */
    public function contextForForm(FormDefinition $form): string
    {
        return $form->slug === 'contact-us' ? 'contact' : 'forms';
    }

    /**
     * Verify one submission. Runs the baseline honeypot + time-trap first (a
     * trip there is always a rejection), then the active provider where enabled,
     * applying the fail policy to a provider outage.
     */
    public function verify(Request $request, string $context): CaptchaVerdict
    {
        // 1. Always-on baseline. Independent of the provider and never fail-open.
        $baseline = HoneypotGuard::check($request);
        if (! $baseline->passed) {
            return CaptchaVerdict::deny($baseline->message, 'baseline');
        }

        // 2. Selected provider, only where the context toggle is on.
        if (! $this->providerActiveFor($context)) {
            return CaptchaVerdict::allow();
        }

        $result = $this->active()->verify($request);

        if ($result->passed) {
            return CaptchaVerdict::allow(false, $this->active()->key());
        }

        // A reachable failure is a wrong/absent answer: always reject.
        if ($result->reachable) {
            return CaptchaVerdict::deny($result->message ?: 'Verification failed. Please try again.', $this->active()->key());
        }

        // Service outage: the fail policy decides. Login closed, content open.
        if (CaptchaSettings::failOpen($context)) {
            return CaptchaVerdict::allow(true, $this->active()->key());
        }

        return CaptchaVerdict::deny(
            'We could not verify your submission right now. Please try again in a moment.',
            $this->active()->key()
        );
    }
}
