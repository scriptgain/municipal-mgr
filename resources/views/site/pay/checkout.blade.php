<x-layouts.public title="Payment Details">
    @if ($isTestMode)
        <x-site.test-mode-banner />
    @endif

    <x-site.page-hero title="Payment Details"
                      eyebrow="Online Payments"
                      icon="lock"
                      subtitle="Your card details go straight to our payment processor. They are never stored on this site."
                      :crumbs="[['label' => 'Pay Your Bill', 'href' => route('site.pay.index')], ['label' => 'Payment']]" />

    <x-site.section :divider="false">
        <div class="mx-auto max-w-xl">
            {{-- What is being paid. Server-computed; the card form cannot change it. --}}
            <div class="mb-6 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm">
                <div class="flex items-center justify-between gap-4 px-6 py-5">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">You Are Paying</p>
                        <p class="mt-1 font-medium text-slate-900">
                            @if ($bill)
                                {{ $bill->type?->label }}
                                <span class="block text-sm tabular text-slate-600">Bill {{ $bill->reference }}</span>
                            @else
                                {{ $payment->type?->label ?? 'Payment' }}
                                @if ($payment->notes)
                                    <span class="block text-sm text-slate-600">{{ $payment->notes }}</span>
                                @endif
                            @endif
                        </p>
                    </div>
                    <span class="shrink-0 text-2xl font-semibold tabular text-brand-800">{{ $amountLabel }}</span>
                </div>
            </div>

            @if ($isTestMode)
                <div class="mb-6 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">
                    <p class="font-semibold">Test Mode</p>
                    <p class="mt-0.5">
                        Use Stripe's test card <span class="font-mono tabular">4242 4242 4242 4242</span>,
                        any future expiry date, and any three digit security code. No money will move.
                    </p>
                </div>
            @endif

            <div id="payment-form"
                 data-publishable-key="{{ $publishableKey }}"
                 data-client-secret="{{ $clientSecret }}"
                 data-return-url="{{ $returnUrl }}"
                 data-connected-account="{{ $connectAccountId }}"
                 class="rounded-2xl bg-white p-6 sm:p-8 ring-1 ring-slate-200 shadow-sm">

                <div id="payment-error" role="alert"
                     class="hidden mb-5 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-rose-200">
                    <div class="flex items-start gap-2.5">
                        <x-icon name="warning" class="mt-0.5 w-4 h-4 shrink-0" aria-hidden="true" />
                        <span id="payment-error-text"></span>
                    </div>
                </div>

                <form novalidate>
                    <h2 class="text-base font-semibold text-slate-900">Card Details</h2>

                    {{-- Stripe Elements mounts here. The card fields live inside a
                         Stripe-hosted iframe, so card numbers never reach this
                         page or this server. --}}
                    <div id="payment-element" class="mt-4 min-h-[220px]"></div>

                    <button type="submit" id="payment-submit"
                            class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 py-3.5 text-base font-semibold text-white transition hover:bg-brand-800 disabled:opacity-60 disabled:pointer-events-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
                        <svg id="payment-spinner" class="hidden h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span id="payment-submit-label">Pay {{ $amountLabel }}</span>
                    </button>

                    <p class="mt-4 flex items-start gap-2 text-xs leading-relaxed text-slate-500">
                        <x-icon name="lock" class="mt-0.5 w-3.5 h-3.5 shrink-0" aria-hidden="true" />
                        <span>
                            Payments are processed securely by Stripe. This site never sees or stores your full
                            card number. Do not refresh this page while your payment is processing.
                        </span>
                    </p>
                </form>
            </div>

            <p class="mt-6 text-center text-sm text-slate-500">
                Changed your mind?
                <a href="{{ route('site.pay.index') }}" class="font-medium text-brand-700 hover:underline">Cancel this payment</a>.
                Your card has not been charged yet.
            </p>
        </div>
        {{-- Loaded here rather than pushed to a stack: the public layout has no
             script stack, and adding one would mean editing a shared layout
             this module does not own. Stripe.js must come from Stripe's own
             domain; that is a PCI requirement, not a preference. --}}
        <script src="https://js.stripe.com/v3/"></script>
        <script src="{{ asset_v('js/payments.js') }}" defer></script>
    </x-site.section>
</x-layouts.public>
