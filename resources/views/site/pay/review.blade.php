<x-layouts.public title="Review Your Bill">
    @if ($isTestMode)
        <x-site.test-mode-banner />
    @endif

    <x-site.page-hero title="Review Your Bill"
                      eyebrow="Online Payments"
                      icon="file-text"
                      subtitle="Check the details below, then continue to payment."
                      :crumbs="[['label' => 'Pay Your Bill', 'href' => route('site.pay.index')], ['label' => 'Review']]" />

    <x-site.section :divider="false">
        @if (! $isReady)
            <x-site.payments-unavailable :supportEmail="$supportEmail" :supportPhone="$supportPhone" />
        @else
            <div class="mx-auto max-w-2xl">
                @error('payment')
                    <div role="alert" class="mb-6 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-rose-200">
                        {{ $message }}
                    </div>
                @enderror

                {{-- The bill --}}
                <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bill Reference</p>
                            <p class="mt-0.5 font-semibold tabular text-slate-900">{{ $bill->reference }}</p>
                        </div>
                        <x-badge :color="$bill->statusColor()" dot>{{ $bill->isOverdue() ? 'Overdue' : $bill->statusLabel() }}</x-badge>
                    </div>

                    <dl class="divide-y divide-slate-100">
                        <div class="flex items-start justify-between gap-4 px-6 py-4">
                            <dt class="text-sm text-slate-500">What This Is For</dt>
                            <dd class="text-right text-sm font-medium text-slate-900">
                                {{ $bill->type?->label }}
                                @if ($bill->description)
                                    <span class="block font-normal text-slate-600">{{ $bill->description }}</span>
                                @endif
                            </dd>
                        </div>

                        @if ($bill->account_number)
                            <div class="flex items-start justify-between gap-4 px-6 py-4">
                                <dt class="text-sm text-slate-500">Account Number</dt>
                                <dd class="text-right text-sm tabular text-slate-900">{{ $bill->account_number }}</dd>
                            </div>
                        @endif

                        @if ($bill->payer_name)
                            <div class="flex items-start justify-between gap-4 px-6 py-4">
                                <dt class="text-sm text-slate-500">Billed To</dt>
                                <dd class="text-right text-sm text-slate-900">{{ $bill->payer_name }}</dd>
                            </div>
                        @endif

                        <div class="flex items-start justify-between gap-4 px-6 py-4">
                            <dt class="text-sm text-slate-500">Due Date</dt>
                            <dd class="text-right text-sm {{ $bill->isOverdue() ? 'font-semibold text-rose-700' : 'text-slate-900' }}">
                                {{ $bill->dueLabel() }}
                            </dd>
                        </div>

                        <div class="flex items-start justify-between gap-4 px-6 py-4">
                            <dt class="text-sm text-slate-500">Bill Total</dt>
                            <dd class="text-right text-sm tabular text-slate-900">{{ $bill->amountFormatted() }}</dd>
                        </div>

                        @if ($bill->amount_paid_cents > 0)
                            <div class="flex items-start justify-between gap-4 px-6 py-4">
                                <dt class="text-sm text-slate-500">Already Paid</dt>
                                <dd class="text-right text-sm tabular text-emerald-700">- {{ $bill->paidFormatted() }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="flex items-center justify-between gap-4 border-t-2 border-slate-200 bg-brand-50/60 px-6 py-5">
                        <span class="text-base font-semibold text-slate-900">Amount To Pay Now</span>
                        <span class="text-2xl font-semibold tabular text-brand-800">{{ $bill->balanceFormatted() }}</span>
                    </div>
                </div>

                {{-- Contact details for the receipt --}}
                <form method="POST" action="{{ route('site.pay.start') }}" class="mt-6 rounded-2xl bg-white p-6 sm:p-8 ring-1 ring-slate-200 shadow-sm">
                    @csrf

                    <h2 class="text-base font-semibold text-slate-900">Where Should We Send Your Receipt?</h2>
                    <p class="mt-1 text-sm text-slate-600">Optional, but it is the easiest way to keep proof of payment.</p>

                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="payer_name" class="block text-sm font-medium text-slate-700">Your Name</label>
                            <input id="payer_name" name="payer_name" type="text" maxlength="150"
                                   value="{{ old('payer_name', $bill->payer_name) }}" autocomplete="name"
                                   class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                        </div>
                        <div>
                            <label for="payer_email" class="block text-sm font-medium text-slate-700">Email Address</label>
                            <input id="payer_email" name="payer_email" type="email" maxlength="150"
                                   value="{{ old('payer_email', $bill->payer_email) }}" autocomplete="email"
                                   class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                            @error('payer_email')
                                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="section-divider mt-6 pt-6 flex flex-wrap items-center justify-between gap-3">
                        <a href="{{ route('site.pay.lookup') }}"
                           class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 transition hover:text-brand-700">
                            <x-icon name="chevron-left" class="w-4 h-4" aria-hidden="true" />
                            Look Up A Different Bill
                        </a>
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-brand-700 px-6 py-3.5 text-base font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
                            Continue To Payment
                            <x-icon name="chevron-right" class="w-4 h-4" aria-hidden="true" />
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </x-site.section>
</x-layouts.public>
