<?php

namespace App\Services\Captcha;

use App\Models\Setting;

/**
 * Typed accessor over the DB Setting store for spam protection.
 *
 * Every knob the feature owns lives here with a sensible default, so a fresh
 * install (and the boot path, before an admin ever opens the screen) always has
 * a working configuration. Keys live in the DB, never .env — the fleet rule.
 */
class CaptchaSettings
{
    /**
     * Defaults. The active provider is the built-in challenge because it needs
     * no external keys, so the protection works the moment the product is
     * installed. The baseline honeypot + time-trap is not in this list: it is
     * always on and cannot be switched off (see HoneypotGuard).
     */
    public const DEFAULTS = [
        'captcha_provider' => 'builtin',
        'captcha_on_login' => '1',
        'captcha_on_report' => '1',
        'captcha_on_contact' => '1',
        'captcha_on_forms' => '1',
        'captcha_min_seconds' => '2',      // time-trap floor; 0 disables the timer
        'captcha_v3_threshold' => '0.5',   // reCAPTCHA v3 pass mark
        'captcha_fail_login' => 'closed',  // login: reject on a service outage
        'captcha_fail_public' => 'open',   // content forms: let residents through
        'captcha_builtin_mode' => 'arithmetic',

        // Provider keys. Seeded with the vendors' OFFICIAL public TEST keys so
        // the feature demonstrates itself immediately. They must be replaced
        // before go-live; the settings screen says so in as many words.
        'captcha_recaptcha_v2_site' => '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
        'captcha_recaptcha_v2_secret' => '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe',
        'captcha_recaptcha_v3_site' => '',
        'captcha_recaptcha_v3_secret' => '',
        'captcha_hcaptcha_site' => '10000000-ffff-ffff-ffff-000000000001',
        'captcha_hcaptcha_secret' => '0x0000000000000000000000000000000000000000',
        'captcha_turnstile_site' => '1x00000000000000000000AA',
        'captcha_turnstile_secret' => '1x0000000000000000000000000000000AA',
    ];

    /** Secret keys are never echoed back into the page. */
    public const SECRET_KEYS = [
        'captcha_recaptcha_v2_secret',
        'captcha_recaptcha_v3_secret',
        'captcha_hcaptcha_secret',
        'captcha_turnstile_secret',
    ];

    public static function get(string $key, $default = null)
    {
        $value = Setting::get($key);
        if ($value === null || $value === '') {
            return $default ?? (self::DEFAULTS[$key] ?? null);
        }

        return $value;
    }

    public static function activeProviderKey(): string
    {
        return (string) self::get('captcha_provider', 'builtin');
    }

    public static function minSeconds(): int
    {
        return max(0, (int) self::get('captcha_min_seconds', 2));
    }

    public static function v3Threshold(): float
    {
        return (float) self::get('captcha_v3_threshold', 0.5);
    }

    public static function enabledFor(string $context): bool
    {
        $key = 'captcha_on_' . $context;

        return (string) self::get($key, self::DEFAULTS[$key] ?? '1') === '1';
    }

    /** Login fails closed by default; public content forms fail open. */
    public static function failOpen(string $context): bool
    {
        $policyKey = $context === 'login' ? 'captcha_fail_login' : 'captcha_fail_public';

        return (string) self::get($policyKey) === 'open';
    }

    /** Persist a batch. Blank secrets are ignored so a save never wipes a key. */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }
            if (in_array($key, self::SECRET_KEYS, true) && ($value === null || $value === '')) {
                continue; // keep the stored secret when the field is left blank
            }
            Setting::put($key, $value === null ? '' : (string) $value);
        }
    }
}
