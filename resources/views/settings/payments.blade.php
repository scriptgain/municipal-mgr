<x-layouts.app title="Payments">
    <x-page-header title="Payments" icon="database"
                   subtitle="Let residents pay bills, permit fees and citations online. Off until you switch it on.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" :href="route('settings.index')">Settings</x-button>
            @if ($isEnabled)
                <x-button variant="secondary" icon="database" :href="route('bills.index')">Bills</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-800 ring-1 ring-brand-200">{{ session('status') }}</div>
    @endif
    @if (session('warning'))
        <div class="mb-5 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">{{ session('warning') }}</div>
    @endif

    {{-- ============================================================
         Module state. The first thing on the page, because it is the
         question the operator came here to answer.
    ============================================================= --}}
    <x-card>
        <div class="flex flex-wrap items-start justify-between gap-5">
            <div class="flex items-start gap-4 min-w-0">
                <span @class([
                    'inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ring-1 shadow-sm',
                    'bg-white text-emerald-600 ring-emerald-200' => $isEnabled,
                    'bg-white text-slate-400 ring-slate-200' => ! $isEnabled,
                ])>
                    <x-icon :name="$isEnabled ? 'check-circle' : 'lock'" class="w-6 h-6" aria-hidden="true" />
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold text-slate-900">Online Payments</h2>
                        @if ($isEnabled)
                            <x-badge color="success" dot>Switched On</x-badge>
                            @if ($isTestMode)
                                <x-badge color="warn" dot>Test Mode</x-badge>
                            @else
                                <x-badge color="danger" dot>Live Mode</x-badge>
                            @endif
                        @else
                            <x-badge color="neutral" dot>Switched Off</x-badge>
                        @endif
                    </div>
                    <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-600">
                        @if ($isEnabled && $isTestMode)
                            Residents can reach the payment pages, and every page carries a loud test-mode warning.
                            No real money will move until you switch to live mode.
                        @elseif ($isEnabled)
                            Residents can pay online with a real card. Money goes directly to your connected
                            Stripe account.
                        @else
                            Nothing about payments is visible on your public site, and the payment pages return a
                            not-found response. This settings screen is the only payments screen that exists.
                        @endif
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('settings.payments.toggle') }}" class="shrink-0">
                @csrf
                <input type="hidden" name="payments_module_enabled" value="{{ $isEnabled ? 0 : 1 }}">
                @if ($isEnabled)
                    <x-button type="submit" variant="secondary" icon="lock">Switch Payments Off</x-button>
                @elseif (count($blockers) === 0)
                    <x-button type="submit" icon="check">Switch Payments On</x-button>
                @else
                    <x-button type="button" icon="lock" disabled>Switch Payments On</x-button>
                @endif
            </form>
        </div>

        {{-- What is standing in the way. Named specifically, never "cannot enable". --}}
        @if (! $isEnabled && count($blockers))
            <div class="section-divider mt-5 pt-5">
                <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                    <x-icon name="warning" class="w-4 h-4 text-amber-500" aria-hidden="true" />
                    Before You Can Switch This On
                </h3>
                <ol class="mt-3 space-y-2">
                    @foreach ($blockers as $blocker)
                        <li class="flex items-start gap-2.5 text-sm text-slate-700">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500" aria-hidden="true"></span>
                            {{ $blocker }}
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
    </x-card>

    <div class="section-divider my-6"></div>

    <x-tabs :tabs="[
        'account' => ['label' => 'Stripe Account', 'icon' => 'building'],
        'keys' => ['label' => 'API Keys', 'icon' => 'key'],
        'webhook' => ['label' => 'Webhook', 'icon' => 'bolt'],
        'presentation' => ['label' => 'Receipts And Wording', 'icon' => 'edit'],
    ]">

        {{-- ========================================================
             Stripe Connect
        ========================================================= --}}
        <x-tab-panel name="account">
            <x-card title="Where The Money Goes"
                    subtitle="Payments settle directly into your municipality's own Stripe account. ScriptGain is never the merchant of record and never handles your funds.">

                <div class="flex flex-wrap items-start justify-between gap-5">
                    <div class="flex items-start gap-4 min-w-0">
                        <span @class([
                            'inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white ring-1 shadow-sm',
                            'text-emerald-600 ring-emerald-200' => $connect['color'] === 'success',
                            'text-amber-600 ring-amber-200' => $connect['color'] === 'warn',
                            'text-rose-600 ring-rose-200' => $connect['color'] === 'danger',
                            'text-slate-400 ring-slate-200' => $connect['color'] === 'neutral',
                        ])>
                            <x-icon name="building" class="w-5 h-5" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold text-slate-900">Connected Account</h3>
                                <x-badge :color="$connect['color']" dot>{{ $connect['label'] }}</x-badge>
                            </div>
                            <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-600">{{ $connect['message'] }}</p>

                            @if ($connectStatus['account_id'])
                                <dl class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Account ID</dt>
                                        <dd class="mt-0.5 font-mono text-xs text-slate-700">{{ $connectStatus['account_id'] }}</dd>
                                    </div>
                                    @if ($connectStatus['business_name'])
                                        <div>
                                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Business Name</dt>
                                            <dd class="mt-0.5 text-slate-700">{{ $connectStatus['business_name'] }}</dd>
                                        </div>
                                    @endif
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Can Accept Charges</dt>
                                        <dd class="mt-0.5">
                                            <x-status-dot :color="$connectStatus['charges_enabled'] ? 'success' : 'danger'"
                                                          :label="$connectStatus['charges_enabled'] ? 'Yes' : 'No'" />
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Can Receive Payouts</dt>
                                        <dd class="mt-0.5">
                                            <x-status-dot :color="$connectStatus['payouts_enabled'] ? 'success' : 'warn'"
                                                          :label="$connectStatus['payouts_enabled'] ? 'Yes' : 'Not Yet'" />
                                        </dd>
                                    </div>
                                    @if ($connectStatus['synced_at'])
                                        <div class="min-w-0 sm:col-span-2">
                                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Status Last Checked</dt>
                                            <dd class="mt-0.5 text-slate-700">{{ $connectStatus['synced_at'] }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="section-divider mt-5 pt-5 flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('settings.payments.connect') }}">
                        @csrf
                        <x-button type="submit" icon="external">
                            {{ $connect['state'] === 'not_connected' ? 'Connect A Stripe Account' : 'Continue Stripe Onboarding' }}
                        </x-button>
                    </form>

                    @if ($connectStatus['account_id'])
                        <form method="POST" action="{{ route('settings.payments.connect.refresh') }}">
                            @csrf
                            <x-button type="submit" variant="secondary" icon="refresh">Refresh Status</x-button>
                        </form>

                        <form method="POST" action="{{ route('settings.payments.connect.dashboard') }}">
                            @csrf
                            <x-button type="submit" variant="secondary" icon="external">Open Stripe Dashboard</x-button>
                        </form>

                        <x-confirm-action name="disconnect-stripe"
                                          :action="route('settings.payments.disconnect')"
                                          title="Disconnect This Stripe Account?"
                                          message="This forgets the connected account and switches payments off. It does not close or change anything at Stripe, and no payment records are deleted. You can reconnect later."
                                          confirm="Disconnect Account"
                                          confirmVariant="danger"
                                          confirmIcon="x-circle"
                                          tone="danger">
                            <x-button variant="ghost" icon="x-circle">Disconnect</x-button>
                        </x-confirm-action>
                    @endif
                </div>

                <div class="section-divider mt-5 pt-5">
                    <p class="text-sm leading-relaxed text-slate-600">
                        <span class="font-semibold text-slate-900">A note on fees.</span>
                        Stripe deducts its processing fee before paying out, so your bank deposit is slightly less
                        than the total charged. This software adds no fee of its own and takes no cut of any
                        payment. Some municipalities pass the processing fee on to the payer as a convenience fee;
                        check your state's rules before doing that.
                    </p>
                </div>
            </x-card>
        </x-tab-panel>

        {{-- ========================================================
             Keys
        ========================================================= --}}
        <x-tab-panel name="keys">
            <form method="POST" action="{{ route('settings.payments.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <x-card title="Test Or Live"
                        subtitle="Test and live are separate Stripe accounts with separate keys. Switching mode swaps which set is used; it never reuses one for the other.">
                    <fieldset>
                        <legend class="sr-only">Payment Mode</legend>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl p-4 ring-1 transition {{ $mode === 'test' ? 'bg-amber-50 ring-amber-300' : 'ring-slate-200 hover:bg-slate-50' }}">
                                <input type="radio" name="mode" value="test" @checked($mode === 'test')
                                       class="mt-1 h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-500">
                                <span class="min-w-0">
                                    <span class="block font-semibold text-slate-900">Test Mode</span>
                                    <span class="mt-0.5 block text-sm text-slate-600">
                                        Nothing is really charged. Every public payment page shows a loud warning.
                                        Start here.
                                    </span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3 rounded-xl p-4 ring-1 transition {{ $mode === 'live' ? 'bg-rose-50 ring-rose-300' : 'ring-slate-200 hover:bg-slate-50' }}">
                                <input type="radio" name="mode" value="live" @checked($mode === 'live')
                                       class="mt-1 h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-500">
                                <span class="min-w-0">
                                    <span class="block font-semibold text-slate-900">Live Mode</span>
                                    <span class="mt-0.5 block text-sm text-slate-600">
                                        Real cards are charged and real money moves. Only switch when you have
                                        tested the whole flow end to end.
                                    </span>
                                </span>
                            </label>
                        </div>
                    </fieldset>
                </x-card>

                <x-card :title="ucfirst($mode) . ' Mode API Keys'"
                        subtitle="From your Stripe dashboard, under Developers then API keys. Make sure you are copying keys for the mode selected above.">
                    <div class="space-y-5">
                        <x-field label="Publishable Key" for="publishable_key"
                                 hint="Starts with pk_test_ or pk_live_. This one is safe to appear in a web page."
                                 :error="$errors->first('publishable_key')">
                            <x-input id="publishable_key" name="publishable_key" :value="$publishableKey"
                                     :placeholder="$mode === 'test' ? 'pk_test_...' : 'pk_live_...'" autocomplete="off" />
                        </x-field>

                        <x-field label="Secret Key" for="secret_key"
                                 :hint="$hasSecretKey
                                    ? 'A secret key is saved. Leave blank to keep it; enter a new one to replace it.'
                                    : 'Starts with sk_test_ or sk_live_. Never share this or paste it anywhere else.'"
                                 :error="$errors->first('secret_key')">
                            <x-input id="secret_key" name="secret_key" type="password"
                                     autocomplete="new-password" data-lpignore="true"
                                     :placeholder="$hasSecretKey ? '••••••••••••••••  (saved)' : ($mode === 'test' ? 'sk_test_...' : 'sk_live_...')" />
                        </x-field>
                    </div>

                    <div class="section-divider mt-5 pt-5">
                        <p class="flex items-start gap-2 text-sm text-slate-600">
                            <x-icon name="lock" class="mt-0.5 w-4 h-4 shrink-0 text-slate-400" aria-hidden="true" />
                            <span>
                                Keys are stored in this site's database, not in a configuration file, and are never
                                shown again once saved. Anyone with your secret key can move money in your Stripe
                                account, so treat it like a bank password.
                            </span>
                        </p>
                    </div>
                </x-card>

                <div class="flex items-center justify-end gap-2">
                    <x-button type="submit" icon="check">Save Payment Settings</x-button>
                </div>
            </form>
        </x-tab-panel>

        {{-- ========================================================
             Webhook
        ========================================================= --}}
        <x-tab-panel name="webhook">
            <x-card title="Webhook Endpoint"
                    subtitle="Stripe calls this address to confirm payments. Without it, a payment can succeed at Stripe while the bill still shows unpaid here.">

                <x-field label="Your Webhook URL" for="webhook_url"
                         hint="Add this as an endpoint in your Stripe dashboard, under Developers then Webhooks.">
                    <x-input id="webhook_url" :value="$webhookUrl" readonly onclick="this.select()"
                             class="font-mono text-xs bg-slate-50" />
                </x-field>

                <div class="section-divider mt-5 pt-5">
                    <h3 class="text-sm font-semibold text-slate-900">Events To Send</h3>
                    <p class="mt-1 text-sm text-slate-600">Select these when you create the endpoint:</p>
                    <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ([
                            'payment_intent.succeeded' => 'Confirms a payment and settles the bill',
                            'payment_intent.payment_failed' => 'Records a declined card',
                            'payment_intent.canceled' => 'Cleans up an abandoned payment',
                            'charge.refunded' => 'Syncs refunds issued from the Stripe dashboard',
                            'payout.paid' => 'Stamps the payout reference for reconciliation',
                            'account.updated' => 'Keeps the connected account status current',
                        ] as $event => $why)
                            <li class="flex items-start gap-2.5 rounded-lg bg-slate-50 px-3 py-2 text-sm ring-1 ring-slate-200">
                                <x-icon name="bolt" class="mt-0.5 w-3.5 h-3.5 shrink-0 text-brand-500" aria-hidden="true" />
                                <span class="min-w-0">
                                    <span class="block font-mono text-xs font-medium text-slate-900">{{ $event }}</span>
                                    <span class="block text-xs text-slate-500">{{ $why }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <form method="POST" action="{{ route('settings.payments.update') }}" class="section-divider mt-5 pt-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="mode" value="{{ $mode }}">

                    <x-field label="Webhook Signing Secret" for="webhook_secret"
                             :hint="$hasWebhookSecret
                                ? 'A signing secret is saved. Leave blank to keep it; enter a new one to replace it.'
                                : 'Starts with whsec_. Shown by Stripe once you have created the endpoint.'"
                             :error="$errors->first('webhook_secret')">
                        <x-input id="webhook_secret" name="webhook_secret" type="password"
                                 autocomplete="new-password" data-lpignore="true"
                                 :placeholder="$hasWebhookSecret ? '••••••••••••••••  (saved)' : 'whsec_...'" />
                    </x-field>

                    <p class="mt-3 flex items-start gap-2 text-sm text-slate-600">
                        <x-icon name="shield" class="mt-0.5 w-4 h-4 shrink-0 text-slate-400" aria-hidden="true" />
                        <span>
                            Every incoming webhook is checked against this secret and rejected if the signature does
                            not match or the request is more than five minutes old. Without a signing secret saved,
                            all webhooks are refused rather than trusted.
                        </span>
                    </p>

                    <div class="mt-5 flex items-center justify-end">
                        <x-button type="submit" icon="check">Save Signing Secret</x-button>
                    </div>
                </form>
            </x-card>
        </x-tab-panel>

        {{-- ========================================================
             Presentation
        ========================================================= --}}
        <x-tab-panel name="presentation">
            <form method="POST" action="{{ route('settings.payments.update') }}" class="space-y-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="mode" value="{{ $mode }}">

                <x-card title="Receipts">
                    <div class="space-y-5">
                        <x-toggle name="payments_email_receipts" :checked="$emailReceipts"
                                  label="Email A Receipt After Every Payment"
                                  description="Sent to the address the resident gave at checkout. They can always download the receipt from the confirmation page as well." />

                        <x-field label="Card Statement Description" for="statement_descriptor"
                                 hint="Appears on the payer's card statement, after your Stripe account's own prefix. Up to 22 characters. Keep it recognisable so residents do not report it as fraud."
                                 :error="$errors->first('statement_descriptor')">
                            <x-input id="statement_descriptor" name="statement_descriptor" maxlength="22"
                                     :value="$statementDescriptor" placeholder="UTILITY BILL" />
                        </x-field>
                    </div>
                </x-card>

                <x-card title="Who Residents Contact"
                        subtitle="Shown on the payment pages and on every receipt. Falls back to your site contact details if left blank.">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="Billing Email Address" for="payments_support_email"
                                 :error="$errors->first('payments_support_email')">
                            <x-input id="payments_support_email" name="payments_support_email" type="email"
                                     :value="$supportEmail" placeholder="utilities@example.gov" />
                        </x-field>

                        <x-field label="Billing Phone Number" for="payments_support_phone"
                                 :error="$errors->first('payments_support_phone')">
                            <x-input id="payments_support_phone" name="payments_support_phone" type="tel"
                                     :value="$supportPhone" placeholder="(928) 555-0100" />
                        </x-field>
                    </div>
                </x-card>

                <x-card title="Payment Page Wording">
                    <x-field label="Introduction Text" for="payments_intro_text"
                             hint="Optional. Shown at the top of the Pay Your Bill page. Use it for anything residents need to know before paying, such as when payments post to their account."
                             :error="$errors->first('payments_intro_text')">
                        <textarea id="payments_intro_text" name="payments_intro_text" rows="3" maxlength="1000"
                                  class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">{{ $introText }}</textarea>
                    </x-field>
                </x-card>

                <div class="flex items-center justify-end gap-2">
                    <x-button type="submit" icon="check">Save Payment Settings</x-button>
                </div>
            </form>

            @if ($isEnabled)
                <x-card class="mt-6" title="Bill Types"
                        subtitle="What residents can pay for, and whether each needs a bill reference.">
                    <p class="text-sm text-slate-600">
                        {{ $billTypeCount }} bill type(s) configured.
                    </p>
                    <div class="mt-4">
                        <x-button variant="secondary" icon="archive" :href="route('bill-types.index')">Manage Bill Types</x-button>
                    </div>
                </x-card>
            @endif
        </x-tab-panel>
    </x-tabs>
</x-layouts.app>
