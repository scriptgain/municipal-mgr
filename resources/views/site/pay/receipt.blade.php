<x-layouts.public title="Payment Receipt">
    @if ($isTestMode)
        <x-site.test-mode-banner />
    @endif

    <x-site.page-hero title="Payment Receipt"
                      eyebrow="Online Payments"
                      icon="check-circle"
                      :crumbs="[['label' => 'Pay Your Bill', 'href' => route('site.pay.index')], ['label' => 'Receipt']]" />

    <x-site.section :divider="false">
        <div class="mx-auto max-w-2xl">

            {{-- Outcome banner. Pending is a real, expected state: the card
                 processor confirms out of band and can take a few seconds. --}}
            @if ($payment->status === 'succeeded' || $payment->status === 'partially_refunded')
                <div role="status" class="mb-6 flex items-start gap-4 rounded-2xl bg-emerald-50 p-6 ring-1 ring-emerald-200">
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-600 ring-1 ring-emerald-200">
                        <x-icon name="check-circle" class="w-6 h-6" aria-hidden="true" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-emerald-900">Payment Received. Thank You.</h2>
                        <p class="mt-1 text-sm leading-relaxed text-emerald-800">
                            Your payment of <span class="font-semibold">{{ $payment->amountFormatted() }}</span> went through.
                            Keep the reference below as your proof of payment.
                        </p>
                    </div>
                </div>
            @elseif ($payment->status === 'pending')
                <div role="status" class="mb-6 flex items-start gap-4 rounded-2xl bg-amber-50 p-6 ring-1 ring-amber-200">
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-amber-600 ring-1 ring-amber-200">
                        <x-icon name="clock" class="w-6 h-6" aria-hidden="true" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-amber-900">Your Payment Is Still Processing</h2>
                        <p class="mt-1 text-sm leading-relaxed text-amber-800">
                            This usually takes only a few seconds. Refresh this page in a moment. Do not pay again:
                            if the payment went through, paying twice would charge you twice.
                        </p>
                        <a href="{{ route('site.pay.receipt', $payment->receipt_token) }}"
                           class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-amber-900 underline underline-offset-2">
                            <x-icon name="refresh" class="w-4 h-4" aria-hidden="true" />
                            Check Again
                        </a>
                    </div>
                </div>
            @elseif ($payment->status === 'refunded')
                <div role="status" class="mb-6 flex items-start gap-4 rounded-2xl bg-slate-100 p-6 ring-1 ring-slate-300">
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-slate-600 ring-1 ring-slate-300">
                        <x-icon name="restore" class="w-6 h-6" aria-hidden="true" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-slate-900">This Payment Was Refunded</h2>
                        <p class="mt-1 text-sm leading-relaxed text-slate-700">
                            {{ $payment->refundedFormatted() }} was returned to your card. Refunds usually appear on
                            your statement within five to ten working days.
                        </p>
                    </div>
                </div>
            @else
                <div role="alert" class="mb-6 flex items-start gap-4 rounded-2xl bg-rose-50 p-6 ring-1 ring-rose-200">
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-rose-600 ring-1 ring-rose-200">
                        <x-icon name="x-circle" class="w-6 h-6" aria-hidden="true" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-rose-900">This Payment Did Not Go Through</h2>
                        <p class="mt-1 text-sm leading-relaxed text-rose-800">
                            {{ $payment->failure_reason ?: 'The payment was not completed.' }}
                            You have not been charged, and the bill is still outstanding.
                        </p>
                        <a href="{{ route('site.pay.index') }}"
                           class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-rose-900 underline underline-offset-2">
                            Try Again
                            <x-icon name="chevron-right" class="w-4 h-4" aria-hidden="true" />
                        </a>
                    </div>
                </div>
            @endif

            {{-- The receipt itself --}}
            <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payment Reference</p>
                        <p class="mt-0.5 text-lg font-semibold tabular text-slate-900">{{ $payment->reference }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($isTestMode)
                            <x-badge color="warn" dot>Test Payment</x-badge>
                        @endif
                        <x-badge :color="$payment->statusColor()" dot>{{ $payment->statusLabel() }}</x-badge>
                    </div>
                </div>

                <dl class="divide-y divide-slate-100">
                    <div class="flex items-start justify-between gap-4 px-6 py-4">
                        <dt class="text-sm text-slate-500">Paid To</dt>
                        <dd class="text-right text-sm font-medium text-slate-900">{{ $siteName }}</dd>
                    </div>

                    <div class="flex items-start justify-between gap-4 px-6 py-4">
                        <dt class="text-sm text-slate-500">What This Was For</dt>
                        <dd class="text-right text-sm text-slate-900">
                            {{ $bill?->type?->label ?? $payment->type?->label ?? 'Payment' }}
                            @if ($bill?->description)
                                <span class="block text-slate-600">{{ $bill->description }}</span>
                            @elseif ($payment->notes)
                                <span class="block text-slate-600">{{ $payment->notes }}</span>
                            @endif
                        </dd>
                    </div>

                    @if ($bill)
                        <div class="flex items-start justify-between gap-4 px-6 py-4">
                            <dt class="text-sm text-slate-500">Bill Reference</dt>
                            <dd class="text-right text-sm tabular text-slate-900">{{ $bill->reference }}</dd>
                        </div>
                    @endif

                    @if ($payment->payer_name)
                        <div class="flex items-start justify-between gap-4 px-6 py-4">
                            <dt class="text-sm text-slate-500">Paid By</dt>
                            <dd class="text-right text-sm text-slate-900">{{ $payment->payer_name }}</dd>
                        </div>
                    @endif

                    <div class="flex items-start justify-between gap-4 px-6 py-4">
                        <dt class="text-sm text-slate-500">Date</dt>
                        <dd class="text-right text-sm text-slate-900">
                            {{ ($payment->paid_at ?? $payment->created_at)->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-4 px-6 py-4">
                        <dt class="text-sm text-slate-500">Payment Method</dt>
                        <dd class="text-right text-sm text-slate-900">{{ $payment->instrumentLabel() }}</dd>
                    </div>

                    @if ($payment->refunded_cents > 0)
                        <div class="flex items-start justify-between gap-4 px-6 py-4">
                            <dt class="text-sm text-slate-500">Refunded</dt>
                            <dd class="text-right text-sm tabular text-slate-700">- {{ $payment->refundedFormatted() }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="flex items-center justify-between gap-4 border-t-2 border-slate-200 bg-brand-50/60 px-6 py-5">
                    <span class="text-base font-semibold text-slate-900">Amount Paid</span>
                    <span class="text-2xl font-semibold tabular text-brand-800">{{ $payment->amountFormatted() }}</span>
                </div>

                @if ($bill && $bill->balanceCents() > 0)
                    <div class="border-t border-slate-100 bg-amber-50 px-6 py-4">
                        <p class="text-sm text-amber-900">
                            <span class="font-semibold">{{ $bill->balanceFormatted() }} is still outstanding</span>
                            on bill {{ $bill->reference }}.
                            <a href="{{ route('site.pay.lookup') }}" class="font-medium underline underline-offset-2">Pay the balance</a>.
                        </p>
                    </div>
                @endif
            </div>

            @if ($payment->isSettled())
                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('site.pay.receipt.download', $payment->receipt_token) }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50">
                        <x-icon name="download" class="w-4 h-4" aria-hidden="true" />
                        Download Receipt
                    </a>
                    <button type="button" onclick="window.print()"
                            class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50">
                        <x-icon name="file-text" class="w-4 h-4" aria-hidden="true" />
                        Print This Page
                    </button>
                </div>

                <p class="mt-6 text-center text-sm text-slate-500">
                    Bookmark this page to come back to your receipt at any time.
                    @if ($payment->payer_email)
                        A copy has also been emailed to {{ $payment->payer_email }}.
                    @endif
                </p>
            @endif

            @if ($supportEmail || $supportPhone)
                <p class="mt-4 text-center text-sm text-slate-500">
                    Questions about this payment:
                    @if ($supportPhone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $supportPhone) }}" class="font-medium text-brand-700 hover:underline">{{ $supportPhone }}</a>
                    @endif
                    @if ($supportEmail && $supportPhone) or @endif
                    @if ($supportEmail)
                        <a href="mailto:{{ $supportEmail }}" class="font-medium text-brand-700 hover:underline">{{ $supportEmail }}</a>
                    @endif
                </p>
            @endif
        </div>
    </x-site.section>
</x-layouts.public>
