<?php

namespace App\Services\Captcha;

use Illuminate\Http\Request;

/**
 * One anti-spam provider.
 *
 * Adding a new provider is exactly this: implement the interface, then register
 * the class in CaptchaManager::registerDefaults(). Nothing else in the app has
 * to change — the settings screen, the <x-captcha> component and the middleware
 * all discover providers through the manager's registry.
 */
interface CaptchaProvider
{
    /** Stable machine key stored in settings (e.g. "recaptcha_v2"). */
    public function key(): string;

    /** Human label for the settings dropdown (Title Case). */
    public function label(): string;

    /** One line describing the trade-off, shown under the label. */
    public function description(): string;

    /**
     * Does this provider load a third-party widget script? Used by the layout
     * so a vendor script is only ever emitted when that provider is active, and
     * by the UI to warn privacy-conscious municipalities.
     */
    public function isThirdParty(): bool;

    /** Are the keys this provider needs actually present? */
    public function isConfigured(): bool;

    /**
     * The absolute URLs of any external widget scripts to load in the page
     * head/body. Empty for the built-in and honeypot-only providers.
     *
     * @return array<int, string>
     */
    public function scripts(): array;

    /**
     * The widget markup rendered inside the form. Returns a plain HTML string
     * (already escaped where it matters); the Blade component echoes it with
     * {!! !!}. Never contains a secret key.
     */
    public function widget(): string;

    /** Verify the submitted challenge server-side. */
    public function verify(Request $request): CaptchaResult;

    /**
     * A real round-trip used by the "Test This Configuration" button. For remote
     * providers this proves the secret and endpoint are reachable; for the
     * built-in provider it proves the sign/verify pipeline works.
     */
    public function selfTest(): CaptchaResult;
}
