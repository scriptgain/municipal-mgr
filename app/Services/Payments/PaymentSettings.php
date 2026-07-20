<?php

namespace App\Services\Payments;

use App\Models\Setting;

/**
 * Typed accessor over the DB Setting store for the payments module.
 *
 * Credentials live in the DB, never in .env, per the fleet's DB-driven config
 * convention. Views never call this directly: controllers and composers read it
 * and hand values to the template.
 *
 * TEST AND LIVE ARE FULLY SEPARATE. Separate secret keys, separate publishable
 * keys, separate webhook secrets and separate connected accounts, because a
 * Stripe test account and a Stripe live account genuinely are different
 * accounts. Flipping the mode switch can therefore never cause a test key to be
 * used against live money or the reverse.
 */
class PaymentSettings
{
    public const KEY_ENABLED = 'payments_module_enabled';
    public const KEY_MODE = 'payments_mode';

    /** Settings that are secrets: never echoed back into a form field. */
    public const SECRET_KEYS = [
        'payments_test_secret_key',
        'payments_test_webhook_secret',
        'payments_live_secret_key',
        'payments_live_webhook_secret',
    ];

    /*
    |--------------------------------------------------------------------------
    | Mode
    |--------------------------------------------------------------------------
    */

    /** 'test' or 'live'. Test is the default, deliberately. */
    public static function mode(): string
    {
        return Setting::get(self::KEY_MODE, 'test') === 'live' ? 'live' : 'test';
    }

    public static function isTestMode(): bool
    {
        return self::mode() === 'test';
    }

    /** Read a value for the ACTIVE mode: secretKey() -> payments_test_secret_key. */
    public static function forMode(string $suffix, $default = null)
    {
        return Setting::get('payments_' . self::mode() . '_' . $suffix, $default);
    }

    public static function putForMode(string $suffix, ?string $value): void
    {
        Setting::put('payments_' . self::mode() . '_' . $suffix, $value);
    }

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    */

    public static function secretKey(): ?string
    {
        return self::nullIfBlank(self::forMode('secret_key'));
    }

    public static function publishableKey(): ?string
    {
        return self::nullIfBlank(self::forMode('publishable_key'));
    }

    public static function webhookSecret(): ?string
    {
        return self::nullIfBlank(self::forMode('webhook_secret'));
    }

    /** The municipality's own Stripe account. Funds land here, not with us. */
    public static function connectAccountId(): ?string
    {
        return self::nullIfBlank(self::forMode('connect_account_id'));
    }

    /*
    |--------------------------------------------------------------------------
    | Connected account status
    |--------------------------------------------------------------------------
    | Cached from the Stripe API by StripeConnect::refresh() so the settings
    | screen does not make a network call on every page load.
    */

    public static function connectStatus(): array
    {
        return [
            'account_id' => self::connectAccountId(),
            'charges_enabled' => self::forMode('connect_charges_enabled') === '1',
            'payouts_enabled' => self::forMode('connect_payouts_enabled') === '1',
            'details_submitted' => self::forMode('connect_details_submitted') === '1',
            'disabled_reason' => self::nullIfBlank(self::forMode('connect_disabled_reason')),
            'requirements_due' => (int) self::forMode('connect_requirements_due', 0),
            'business_name' => self::nullIfBlank(self::forMode('connect_business_name')),
            'synced_at' => self::nullIfBlank(self::forMode('connect_synced_at')),
        ];
    }

    /**
     * One of: not_connected, onboarding_incomplete, restricted, active.
     * Drives the whole settings screen, so it is computed once, here.
     */
    public static function connectState(): string
    {
        $status = self::connectStatus();

        if (! $status['account_id']) {
            return 'not_connected';
        }
        if (! $status['details_submitted']) {
            return 'onboarding_incomplete';
        }
        if (! $status['charges_enabled']) {
            return 'restricted';
        }

        return 'active';
    }

    /*
    |--------------------------------------------------------------------------
    | Gating
    |--------------------------------------------------------------------------
    */

    /**
     * Are the credentials needed to take a payment all present?
     *
     * The enable switch refuses to turn on until this is true, so the module
     * can never be half-enabled into a checkout that 500s on the resident.
     */
    public static function isConfigured(): bool
    {
        return self::secretKey() !== null
            && self::publishableKey() !== null
            && self::connectAccountId() !== null;
    }

    /** Configured AND the connected account can actually accept charges. */
    public static function isReady(): bool
    {
        return self::isConfigured() && self::connectState() === 'active';
    }

    /**
     * The single gate the whole module hangs off.
     *
     * Both conditions are checked every time, not just at enable time: if an
     * operator later clears a credential, the public payment pages must go away
     * rather than stay up and fail at the card form.
     */
    public static function isEnabled(): bool
    {
        return Setting::get(self::KEY_ENABLED, '0') === '1' && self::isConfigured();
    }

    /** Raw switch position, ignoring whether credentials back it up. */
    public static function switchIsOn(): bool
    {
        return Setting::get(self::KEY_ENABLED, '0') === '1';
    }

    /*
    |--------------------------------------------------------------------------
    | Presentation
    |--------------------------------------------------------------------------
    */

    /** Shown on the card statement. Stripe allows 5 to 22 chars. */
    public static function statementDescriptor(): ?string
    {
        $value = self::nullIfBlank(Setting::get('payments_statement_descriptor'));

        return $value ? substr($value, 0, 22) : null;
    }

    public static function supportEmail(): ?string
    {
        return self::nullIfBlank(Setting::get('payments_support_email'))
            ?? self::nullIfBlank(Setting::get('contact_email'));
    }

    public static function supportPhone(): ?string
    {
        return self::nullIfBlank(Setting::get('payments_support_phone'))
            ?? self::nullIfBlank(Setting::get('contact_phone'));
    }

    public static function emailReceipts(): bool
    {
        return Setting::get('payments_email_receipts', '1') === '1';
    }

    /** Intro copy on the public Pay Your Bill landing page. */
    public static function introText(): ?string
    {
        return self::nullIfBlank(Setting::get('payments_intro_text'));
    }

    private static function nullIfBlank($value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return ($value === null || $value === '') ? null : (string) $value;
    }
}
