<?php

namespace App\Services\Payments;

use App\Models\Setting;

/**
 * Stripe Connect onboarding for the municipality's own account.
 *
 * THE POINT OF THIS WHOLE CLASS: the municipality is the merchant of record and
 * money lands in the municipality's Stripe balance. ScriptGain is the platform
 * that supplies the software and is never in the funds flow. No application
 * fee is set anywhere in this module, and no transfer or destination parameter
 * is ever sent. If a future change adds one, it changes that arrangement and
 * should be treated as a legal decision, not a technical one.
 *
 * Accounts are `standard`, so the town holds its own full Stripe account, sees
 * its own dashboard, and owns its own relationship with Stripe.
 */
class StripeConnect
{
    /**
     * Create the connected account if there is not one yet, then return a
     * one-time onboarding link to redirect the operator to.
     *
     * @return array{ok: bool, url: ?string, error: ?string}
     */
    public static function onboardingLink(string $returnUrl, string $refreshUrl, array $prefill = []): array
    {
        $accountId = PaymentSettings::connectAccountId();

        if (! $accountId) {
            $result = StripeGateway::createAccount(array_filter([
                'email' => $prefill['email'] ?? null,
                'business_profile' => array_filter([
                    'name' => $prefill['name'] ?? null,
                    'url' => $prefill['url'] ?? null,
                    // Government services MCC. Stripe uses this for risk and
                    // for how the charge is described to the cardholder's bank.
                    'mcc' => '9399',
                ]),
            ]));

            if (! $result['ok']) {
                return ['ok' => false, 'url' => null, 'error' => $result['error']];
            }

            $accountId = $result['data']['id'] ?? null;

            if (! $accountId) {
                return ['ok' => false, 'url' => null, 'error' => 'Stripe did not return an account id.'];
            }

            PaymentSettings::putForMode('connect_account_id', $accountId);
            self::storeStatus($result['data']);
        }

        $link = StripeGateway::createAccountLink($accountId, $refreshUrl, $returnUrl);

        if (! $link['ok']) {
            return ['ok' => false, 'url' => null, 'error' => $link['error']];
        }

        return ['ok' => true, 'url' => $link['data']['url'] ?? null, 'error' => null];
    }

    /**
     * Re-read the connected account from Stripe and cache what the settings
     * screen needs, so that screen never makes a network call to render.
     *
     * @return array{ok: bool, error: ?string}
     */
    public static function refresh(): array
    {
        $accountId = PaymentSettings::connectAccountId();

        if (! $accountId) {
            return ['ok' => false, 'error' => 'No connected account yet.'];
        }

        $result = StripeGateway::retrieveAccount($accountId);

        if (! $result['ok']) {
            return ['ok' => false, 'error' => $result['error']];
        }

        self::storeStatus($result['data']);

        return ['ok' => true, 'error' => null];
    }

    /** Cache the flags that decide whether the town can actually take money. */
    public static function storeStatus(array $account): void
    {
        $due = count((array) data_get($account, 'requirements.currently_due', []));

        PaymentSettings::putForMode('connect_charges_enabled', ! empty($account['charges_enabled']) ? '1' : '0');
        PaymentSettings::putForMode('connect_payouts_enabled', ! empty($account['payouts_enabled']) ? '1' : '0');
        PaymentSettings::putForMode('connect_details_submitted', ! empty($account['details_submitted']) ? '1' : '0');
        PaymentSettings::putForMode('connect_disabled_reason', (string) data_get($account, 'requirements.disabled_reason', ''));
        PaymentSettings::putForMode('connect_requirements_due', (string) $due);
        PaymentSettings::putForMode('connect_business_name', (string) (data_get($account, 'business_profile.name') ?: ''));
        PaymentSettings::putForMode('connect_synced_at', now()->toDateTimeString());
    }

    /** Forget the connected account for the current mode. Does not touch Stripe. */
    public static function disconnect(): void
    {
        foreach ([
            'connect_account_id', 'connect_charges_enabled', 'connect_payouts_enabled',
            'connect_details_submitted', 'connect_disabled_reason', 'connect_requirements_due',
            'connect_business_name', 'connect_synced_at',
        ] as $suffix) {
            PaymentSettings::putForMode($suffix, '');
        }

        // Disconnecting the account that payments depend on must also take the
        // public module down, or the next resident hits a checkout with nowhere
        // to send the money.
        Setting::put(PaymentSettings::KEY_ENABLED, '0');
    }

    /**
     * Human-readable status for the settings screen.
     * Computed here so the Blade template stays markup only.
     *
     * @return array{state: string, label: string, color: string, message: string}
     */
    public static function statusPanel(): array
    {
        $status = PaymentSettings::connectStatus();

        return match (PaymentSettings::connectState()) {
            'not_connected' => [
                'state' => 'not_connected',
                'label' => 'Not Connected',
                'color' => 'neutral',
                'message' => 'No Stripe account is connected yet. Connect one to let residents pay online. Funds go directly to the account you connect.',
            ],
            'onboarding_incomplete' => [
                'state' => 'onboarding_incomplete',
                'label' => 'Onboarding Incomplete',
                'color' => 'warn',
                'message' => 'Stripe still needs details about your organisation before it will accept payments. Continue onboarding to finish.',
            ],
            'restricted' => [
                'state' => 'restricted',
                'label' => 'Restricted',
                'color' => 'danger',
                'message' => $status['disabled_reason']
                    ? 'Stripe has restricted this account (' . str_replace('_', ' ', $status['disabled_reason']) . '). Payments cannot be taken until it is resolved in the Stripe dashboard.'
                    : 'Stripe is not currently allowing charges on this account. There are ' . $status['requirements_due'] . ' outstanding requirement(s) to clear.',
            ],
            default => [
                'state' => 'active',
                'label' => 'Active',
                'color' => 'success',
                'message' => 'This account can accept payments. Funds settle directly into it on your Stripe payout schedule.',
            ],
        };
    }
}
