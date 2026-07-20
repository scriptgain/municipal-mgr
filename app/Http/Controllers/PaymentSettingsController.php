<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BillType;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Services\Payments\PaymentSettings;
use App\Services\Payments\StripeConnect;
use App\Services\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Settings for the Pay Your Bill module.
 *
 * This is the ONLY payments screen that exists while the module is off, and it
 * is what turns it on. The switch refuses to move until Stripe is configured
 * and the connected account can actually take a charge, because a half-enabled
 * payments module means a resident hitting a broken checkout, which on a
 * government site is the kind of thing that ends up in the local paper.
 */
class PaymentSettingsController extends Controller
{
    public function edit()
    {
        $mode = PaymentSettings::mode();

        return view('settings.payments', [
            'mode' => $mode,
            'isTestMode' => PaymentSettings::isTestMode(),
            'switchIsOn' => PaymentSettings::switchIsOn(),
            'isConfigured' => PaymentSettings::isConfigured(),
            'isReady' => PaymentSettings::isReady(),
            'isEnabled' => PaymentSettings::isEnabled(),
            'connect' => StripeConnect::statusPanel(),
            'connectStatus' => PaymentSettings::connectStatus(),

            // Secrets are never echoed back into the form. The view renders a
            // "saved" indicator and an empty box; leaving it blank keeps the
            // stored value.
            'hasSecretKey' => PaymentSettings::secretKey() !== null,
            'hasWebhookSecret' => PaymentSettings::webhookSecret() !== null,
            'publishableKey' => PaymentSettings::publishableKey(),

            'webhookUrl' => route('payments.webhook'),
            'statementDescriptor' => PaymentSettings::statementDescriptor(),
            'supportEmail' => Setting::get('payments_support_email'),
            'supportPhone' => Setting::get('payments_support_phone'),
            'introText' => PaymentSettings::introText(),
            'emailReceipts' => PaymentSettings::emailReceipts(),

            'billTypeCount' => BillType::count(),
            'blockers' => $this->blockers(),
        ]);
    }

    /**
     * Save credentials and presentation settings.
     * Does NOT touch the enable switch: that is its own deliberate action.
     */
    public function update(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $mode = $request->input('mode') === 'live' ? 'live' : 'test';

        $data = $request->validate([
            'publishable_key' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:255'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'statement_descriptor' => ['nullable', 'string', 'max:22'],
            'payments_support_email' => ['nullable', 'email', 'max:150'],
            'payments_support_phone' => ['nullable', 'string', 'max:40'],
            'payments_intro_text' => ['nullable', 'string', 'max:1000'],
        ]);

        $previousMode = PaymentSettings::mode();
        Setting::put(PaymentSettings::KEY_MODE, $mode);

        // Keys are per mode, so write them against the mode the form was
        // submitted for, not whatever mode happened to be active before.
        Setting::put("payments_{$mode}_publishable_key", trim((string) ($data['publishable_key'] ?? '')));

        // Secrets: a blank box means "leave what is stored", never "clear it".
        // Clearing is an explicit separate action.
        foreach (['secret_key', 'webhook_secret'] as $field) {
            $value = trim((string) ($data[$field] ?? ''));
            if ($value !== '') {
                Setting::put("payments_{$mode}_{$field}", $value);
            }
        }

        Setting::put('payments_statement_descriptor', trim((string) ($data['statement_descriptor'] ?? '')));
        Setting::put('payments_support_email', trim((string) ($data['payments_support_email'] ?? '')));
        Setting::put('payments_support_phone', trim((string) ($data['payments_support_phone'] ?? '')));
        Setting::put('payments_intro_text', trim((string) ($data['payments_intro_text'] ?? '')));
        Setting::put('payments_email_receipts', $request->boolean('payments_email_receipts', true) ? '1' : '0');

        // Switching mode changes which connected account is live. If the module
        // was on and the new mode is not configured, take it down rather than
        // leave a checkout pointing at credentials that do not exist.
        if ($previousMode !== $mode && PaymentSettings::switchIsOn() && ! PaymentSettings::isConfigured()) {
            Setting::put(PaymentSettings::KEY_ENABLED, '0');
            $this->repointPayBillLink(false);

            AuditLog::record('payments-disabled', "Payments module disabled: switched to {$mode} mode, which is not configured");

            return redirect()->route('settings.payments.edit')
                ->with('warning', 'Switched To ' . ucfirst($mode) . ' Mode. The Module Was Turned Off Because ' . ucfirst($mode) . ' Mode Is Not Configured Yet.');
        }

        AuditLog::record('updated', "Payment settings updated ({$mode} mode)");

        return redirect()->route('settings.payments.edit')->with('status', 'Payment Settings Saved.');
    }

    /**
     * Turn the module on or off.
     *
     * The gate: enabling is refused unless Stripe is configured AND the
     * connected account can take charges. The response names the specific
     * blocker rather than saying "cannot enable".
     */
    public function toggle(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $wantOn = $request->boolean('payments_module_enabled');

        if (! $wantOn) {
            Setting::put(PaymentSettings::KEY_ENABLED, '0');
            $this->repointPayBillLink(false);

            AuditLog::record('payments-disabled', 'Payments module disabled');

            return redirect()->route('settings.payments.edit')
                ->with('status', 'Payments Are Now Switched Off. The Public Payment Pages Are No Longer Reachable.');
        }

        if ($blockers = $this->blockers()) {
            return redirect()->route('settings.payments.edit')
                ->with('warning', 'Payments Cannot Be Switched On Yet: ' . $blockers[0]);
        }

        // Seed the starting bill types on first enable so staff land on a
        // working screen instead of an empty one.
        if (BillType::count() === 0) {
            $this->seedBillTypes();
        }

        Setting::put(PaymentSettings::KEY_ENABLED, '1');
        $this->repointPayBillLink(true);

        AuditLog::record('payments-enabled', 'Payments module enabled (' . PaymentSettings::mode() . ' mode)');

        return redirect()->route('settings.payments.edit')->with(
            'status',
            PaymentSettings::isTestMode()
                ? 'Payments Are Now Switched On In TEST Mode. No Real Money Will Move.'
                : 'Payments Are Now Switched On In LIVE Mode. Real Cards Will Be Charged.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Stripe Connect
    |--------------------------------------------------------------------------
    */

    /** Start or resume Stripe onboarding for the municipality's own account. */
    public function connect()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if (! PaymentSettings::secretKey()) {
            return back()->with('warning', 'Enter Your Stripe Secret Key And Save Before Connecting An Account.');
        }

        $site = SiteSettings::all();

        $result = StripeConnect::onboardingLink(
            returnUrl: route('settings.payments.connect.return'),
            refreshUrl: route('settings.payments.connect'),
            prefill: [
                'email' => $site['contact_email'] ?? null,
                'name' => SiteSettings::formalName(),
                'url' => config('app.url'),
            ]
        );

        if (! $result['ok'] || ! $result['url']) {
            return back()->with('warning', 'Stripe Could Not Start Onboarding: ' . ($result['error'] ?? 'Unknown error.'));
        }

        AuditLog::record('payments-connect', 'Stripe Connect onboarding started (' . PaymentSettings::mode() . ' mode)');

        return redirect()->away($result['url']);
    }

    /** Where Stripe returns the operator after onboarding. */
    public function connectReturn()
    {
        $result = StripeConnect::refresh();

        if (! $result['ok']) {
            return redirect()->route('settings.payments.edit')
                ->with('warning', 'Could Not Read The Account Status From Stripe: ' . $result['error']);
        }

        $state = PaymentSettings::connectState();

        return redirect()->route('settings.payments.edit')->with(
            $state === 'active' ? 'status' : 'warning',
            match ($state) {
                'active' => 'Stripe Account Connected. You Can Now Switch Payments On.',
                'onboarding_incomplete' => 'Stripe Still Needs More Details Before This Account Can Take Payments.',
                'restricted' => 'Stripe Has Restricted This Account. Resolve The Outstanding Requirements In Your Stripe Dashboard.',
                default => 'Account Status Refreshed.',
            }
        );
    }

    /** Re-read the connected account status on demand. */
    public function refreshConnect()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $result = StripeConnect::refresh();

        return back()->with(
            $result['ok'] ? 'status' : 'warning',
            $result['ok'] ? 'Stripe Account Status Refreshed.' : ($result['error'] ?? 'Could not refresh.')
        );
    }

    /** Open the connected account's own Stripe dashboard. */
    public function dashboardLink()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $accountId = PaymentSettings::connectAccountId();

        if (! $accountId) {
            return back()->with('warning', 'No Stripe Account Is Connected Yet.');
        }

        $result = \App\Services\Payments\StripeGateway::createLoginLink($accountId);

        // Standard accounts log in at stripe.com directly; the login-link API is
        // only available for Express. Falling back to the dashboard is correct
        // rather than an error.
        if (! $result['ok'] || empty($result['data']['url'])) {
            return redirect()->away('https://dashboard.stripe.com/');
        }

        return redirect()->away($result['data']['url']);
    }

    /** Forget the connected account. Does not delete anything at Stripe. */
    public function disconnect()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        StripeConnect::disconnect();
        $this->repointPayBillLink(false);

        AuditLog::record('payments-disconnect', 'Stripe connected account removed (' . PaymentSettings::mode() . ' mode)');

        return redirect()->route('settings.payments.edit')
            ->with('status', 'Stripe Account Disconnected And Payments Switched Off.');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Everything standing between the operator and switching payments on, in
     * the order they should fix them. Computed here so the Blade template can
     * just list them.
     */
    private function blockers(): array
    {
        $blockers = [];

        if (! PaymentSettings::secretKey()) {
            $blockers[] = 'Enter your Stripe secret key for ' . PaymentSettings::mode() . ' mode.';
        }
        if (! PaymentSettings::publishableKey()) {
            $blockers[] = 'Enter your Stripe publishable key for ' . PaymentSettings::mode() . ' mode.';
        }
        if (! PaymentSettings::connectAccountId()) {
            $blockers[] = 'Connect the Stripe account that should receive the money.';
        } elseif (PaymentSettings::connectState() !== 'active') {
            $blockers[] = 'Finish Stripe onboarding: the connected account cannot accept charges yet.';
        }
        if (! PaymentSettings::webhookSecret()) {
            $blockers[] = 'Add the webhook signing secret so payment confirmations can be verified.';
        }

        return $blockers;
    }

    /**
     * Point the existing "Pay A Bill" quick link at the real payment page when
     * the module goes live, and back at Contact when it goes away.
     *
     * The link already exists on the public site as a placeholder pointing at
     * /contact. Repointing it is the whole of the module's public navigation
     * footprint: the primary nav was just regrouped down to three dropdowns and
     * does not need a fourth item.
     */
    private function repointPayBillLink(bool $enabled): void
    {
        rescue(function () use ($enabled) {
            // Absolute, to match how every other quick link on this site is
            // stored. A relative URL would still work, but the menu editor
            // would then show one odd row among five consistent ones.
            $target = $enabled ? url('/pay') : url('/contact');

            MenuItem::where('menu', 'quicklinks')
                ->where(fn ($q) => $q->where('label', 'like', '%Pay%Bill%')->orWhere('label', 'like', '%Pay Your%'))
                ->get()
                ->each(function (MenuItem $item) use ($target) {
                    // page_id must be cleared too: href() prefers a linked CMS
                    // page over the typed URL, so leaving it set would silently
                    // ignore the repoint.
                    $item->forceFill(['url' => $target, 'page_id' => null])->save();
                });

            // The public layout caches its menus for two minutes; clear so the
            // change is visible immediately rather than "sometimes".
            foreach (['primary', 'utility', 'footer', 'quicklinks'] as $menu) {
                Cache::forget("site.menu.{$menu}");
            }
        }, null, false);
    }

    /** Seed the starting bill types from config on first enable. */
    private function seedBillTypes(): void
    {
        foreach (config('payments.default_bill_types', []) as $i => $type) {
            BillType::create($type + ['sort_order' => $i * 10, 'is_active' => true]);
        }
    }
}
