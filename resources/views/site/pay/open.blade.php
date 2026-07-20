<x-layouts.public :title="$type->label">
    @if ($isTestMode)
        <x-site.test-mode-banner />
    @endif

    <x-site.page-hero :title="$type->label"
                      eyebrow="Online Payments"
                      :icon="$type->icon"
                      :subtitle="$type->description"
                      :crumbs="[['label' => 'Pay Your Bill', 'href' => route('site.pay.index')], ['label' => $type->label]]" />

    <x-site.section :divider="false">
        @if (! $isReady)
            <x-site.payments-unavailable />
        @else
            <div class="mx-auto max-w-xl">
                <form method="POST" action="{{ route('site.pay.open.start', $type->key) }}"
                      class="rounded-2xl bg-white p-8 ring-1 ring-slate-200 shadow-sm">
                    @csrf

                    {{-- Honeypot: bots fill it, humans never see it. --}}
                    <div class="hidden" aria-hidden="true">
                        <label for="website">Leave This Blank</label>
                        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label for="amount" class="block text-sm font-medium text-slate-700">
                                Amount To Pay <span class="text-rose-600" aria-hidden="true">*</span>
                            </label>
                            <div class="relative mt-1.5">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-slate-500" aria-hidden="true">$</span>
                                <input id="amount" name="amount" type="text" inputmode="decimal" required
                                       value="{{ old('amount') }}" placeholder="0.00"
                                       aria-describedby="amount-hint"
                                       class="block w-full rounded-lg border-0 py-2.5 pl-7 pr-3 text-sm tabular ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                            </div>
                            <p id="amount-hint" class="mt-1.5 text-sm text-slate-500">
                                Between {{ $minLabel }} and {{ $maxLabel }}. Check the fee schedule or your
                                application letter if you are not sure what to enter.
                            </p>
                            @error('amount')
                                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="memo" class="block text-sm font-medium text-slate-700">What Is This Payment For?</label>
                            <input id="memo" name="memo" type="text" maxlength="200"
                                   value="{{ old('memo') }}" placeholder="Fence permit, 214 Mesquite Lane"
                                   aria-describedby="memo-hint"
                                   class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                            <p id="memo-hint" class="mt-1.5 text-sm text-slate-500">
                                Optional, but it helps staff match your payment to your application.
                            </p>
                            @error('memo')
                                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="section-divider pt-5">
                            <h2 class="text-base font-semibold text-slate-900">Your Details</h2>
                            <p class="mt-1 text-sm text-slate-600">So we can send your receipt and match the payment to your record.</p>
                        </div>

                        <div>
                            <label for="payer_name" class="block text-sm font-medium text-slate-700">
                                Your Name <span class="text-rose-600" aria-hidden="true">*</span>
                            </label>
                            <input id="payer_name" name="payer_name" type="text" required maxlength="150"
                                   value="{{ old('payer_name') }}" autocomplete="name"
                                   class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                            @error('payer_name')
                                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="payer_email" class="block text-sm font-medium text-slate-700">
                                Email Address <span class="text-rose-600" aria-hidden="true">*</span>
                            </label>
                            <input id="payer_email" name="payer_email" type="email" required maxlength="150"
                                   value="{{ old('payer_email') }}" autocomplete="email"
                                   class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                            @error('payer_email')
                                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="payer_phone" class="block text-sm font-medium text-slate-700">Phone Number</label>
                            <input id="payer_phone" name="payer_phone" type="tel" maxlength="40"
                                   value="{{ old('payer_phone') }}" autocomplete="tel"
                                   class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                        </div>

                        <button type="submit"
                                class="w-full rounded-lg bg-brand-700 px-5 py-3.5 text-base font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
                            Continue To Payment
                        </button>
                    </div>

                    <p class="section-divider mt-6 pt-5 text-sm text-slate-500">
                        Paying an invoice you were sent? Use
                        <a href="{{ route('site.pay.lookup') }}" class="font-medium text-brand-700 hover:underline">Find Your Bill</a>
                        instead, so the payment is applied to it automatically.
                    </p>
                </form>
            </div>
        @endif
    </x-site.section>
</x-layouts.public>
