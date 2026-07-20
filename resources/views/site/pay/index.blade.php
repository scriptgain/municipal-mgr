<x-layouts.public title="Pay Your Bill" description="Pay your utility bill, permit fees and citations online.">
    @if ($isTestMode)
        <x-site.test-mode-banner />
    @endif

    <x-site.page-hero title="Pay Your Bill"
                      eyebrow="Online Payments"
                      icon="database"
                      subtitle="Pay a utility bill, permit fee or citation online. You do not need an account."
                      :crumbs="[['label' => 'Pay Your Bill']]" />

    @if (! $isReady)
        <x-site.section :divider="false">
            <x-site.payments-unavailable :supportEmail="$supportEmail" :supportPhone="$supportPhone" />
        </x-site.section>
    @else
        {{-- Pay a bill you have a reference for --}}
        <x-site.section title="Pay A Bill You Have Received"
                        subtitle="You will need the reference number printed on your bill, and the last name or ZIP code on the account.">
            @if ($intro)
                <p class="mb-8 max-w-3xl text-lg leading-relaxed text-slate-600">{{ $intro }}</p>
            @endif

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($types as $type)
                    <a href="{{ route('site.pay.lookup', ['type' => $type->key]) }}"
                       class="group flex items-start gap-4 rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm transition hover:ring-brand-300 hover:shadow-md">
                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-200">
                            <x-icon :name="$type->icon" class="w-5 h-5" aria-hidden="true" />
                        </span>
                        <span class="min-w-0">
                            <span class="block font-semibold text-slate-900 group-hover:text-brand-800">{{ $type->label }}</span>
                            @if ($type->description)
                                <span class="mt-1 block text-sm leading-relaxed text-slate-600">{{ $type->description }}</span>
                            @endif
                            <span class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700">
                                Look Up A Bill
                                <x-icon name="chevron-right" class="w-4 h-4" aria-hidden="true" />
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        </x-site.section>

        {{-- Pay without a bill reference --}}
        @if ($openTypes->count())
            <x-site.section tone="muted"
                            title="Pay Without A Bill Reference"
                            subtitle="For fees where you have not been sent a bill, such as a permit application or a facility rental.">
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($openTypes as $type)
                        <a href="{{ route('site.pay.open', $type->key) }}"
                           class="group flex items-start gap-4 rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm transition hover:ring-brand-300 hover:shadow-md">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-seal-50 text-seal-700 ring-1 ring-seal-200">
                                <x-icon :name="$type->icon" class="w-5 h-5" aria-hidden="true" />
                            </span>
                            <span class="min-w-0">
                                <span class="block font-semibold text-slate-900 group-hover:text-brand-800">{{ $type->label }}</span>
                                @if ($type->description)
                                    <span class="mt-1 block text-sm leading-relaxed text-slate-600">{{ $type->description }}</span>
                                @endif
                                <span class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700">
                                    Make A Payment
                                    <x-icon name="chevron-right" class="w-4 h-4" aria-hidden="true" />
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </x-site.section>
        @endif

        {{-- Reassurance and other ways to pay --}}
        <x-site.section title="How Online Payments Work">
            <div class="grid gap-8 lg:grid-cols-3">
                <div class="flex items-start gap-4">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-200">
                        <x-icon name="lock" class="w-5 h-5" aria-hidden="true" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-slate-900">Your Card Details Stay Private</h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-600">
                            Card details are entered directly with our payment processor and are never stored on
                            this website. We keep only the last four digits, so you can recognise your own payment.
                        </p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-200">
                        <x-icon name="envelope" class="w-5 h-5" aria-hidden="true" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-slate-900">You Get A Receipt</h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-600">
                            Every payment gets a reference number and a receipt you can download or have emailed
                            to you. Keep it: it is your proof of payment.
                        </p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-200">
                        <x-icon name="building" class="w-5 h-5" aria-hidden="true" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-slate-900">Other Ways To Pay</h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-600">
                            You can still pay in person at the town offices or by mail. Online payment is an
                            option, never a requirement.
                            @if ($supportPhone)
                                Questions: <a href="tel:{{ preg_replace('/[^0-9+]/', '', $supportPhone) }}" class="font-medium text-brand-700 hover:underline">{{ $supportPhone }}</a>.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </x-site.section>
    @endif
</x-layouts.public>
