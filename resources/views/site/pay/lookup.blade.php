<x-layouts.public title="Find Your Bill">
    @if ($isTestMode)
        <x-site.test-mode-banner />
    @endif

    <x-site.page-hero title="Find Your Bill"
                      eyebrow="Online Payments"
                      icon="search"
                      subtitle="Enter the reference number from your bill to see what is owed."
                      :crumbs="[['label' => 'Pay Your Bill', 'href' => route('site.pay.index')], ['label' => 'Find Your Bill']]" />

    <x-site.section :divider="false">
        @if (! $isReady)
            <x-site.payments-unavailable />
        @else
            <div class="mx-auto max-w-xl">
                <form method="POST" action="{{ route('site.pay.lookup.submit') }}"
                      class="rounded-2xl bg-white p-8 ring-1 ring-slate-200 shadow-sm">
                    @csrf

                    <div class="space-y-5">
                        <div>
                            <label for="reference" class="block text-sm font-medium text-slate-700">
                                Bill Reference Number <span class="text-rose-600" aria-hidden="true">*</span>
                            </label>
                            <input id="reference" name="reference" type="text" required
                                   value="{{ old('reference') }}" placeholder="BILL-2026-000412"
                                   autocomplete="off" spellcheck="false"
                                   aria-describedby="reference-hint"
                                   class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm tabular ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                            <p id="reference-hint" class="mt-1.5 text-sm text-slate-500">
                                Printed at the top of your bill.
                            </p>
                            @error('reference')
                                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="second_factor" class="block text-sm font-medium text-slate-700">
                                Last Name Or ZIP Code On The Bill <span class="text-rose-600" aria-hidden="true">*</span>
                            </label>
                            <input id="second_factor" name="second_factor" type="text" required
                                   value="{{ old('second_factor') }}" placeholder="Alvarez, or 86326"
                                   autocomplete="off"
                                   aria-describedby="second-factor-hint"
                                   class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                            <p id="second-factor-hint" class="mt-1.5 text-sm text-slate-500">
                                We ask for this so nobody can look up a bill that is not theirs.
                            </p>
                            @error('second_factor')
                                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                                class="w-full rounded-lg bg-brand-700 px-5 py-3.5 text-base font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
                            Find My Bill
                        </button>
                    </div>

                    <p class="section-divider mt-6 pt-5 text-sm text-slate-500">
                        Cannot find your reference number, or think your bill is wrong?
                        <a href="{{ route('site.contact') }}" class="font-medium text-brand-700 hover:underline">Contact the town offices</a>
                        and we will help. You can also pay in person or by mail.
                    </p>
                </form>

                @if ($types->count())
                    <div class="mt-8 rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">What You Can Pay Here</h2>
                        <ul class="mt-3 space-y-2">
                            @foreach ($types as $type)
                                <li class="flex items-start gap-2.5 text-sm text-slate-700">
                                    <x-icon :name="$type->icon" class="mt-0.5 w-4 h-4 shrink-0 text-brand-600" aria-hidden="true" />
                                    <span><span class="font-medium text-slate-900">{{ $type->label }}</span>@if ($type->description): {{ $type->description }}@endif</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif
    </x-site.section>
</x-layouts.public>
