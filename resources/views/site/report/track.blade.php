<x-layouts.public title="Track A Request">
    <x-site.page-hero title="Track A Request"
                      subtitle="Look up an issue you reported using your reference number."
                      :crumbs="[['label' => 'Track A Request']]" />

    <x-site.section :divider="false">
        <div class="mx-auto max-w-xl">
            <form method="POST" action="{{ route('site.track.lookup') }}" class="rounded-2xl bg-white p-8 ring-1 ring-slate-200 shadow-sm">
                @csrf
                <div class="space-y-5">
                    <div>
                        <label for="reference" class="block text-sm font-medium text-slate-700">Reference Number <span class="text-rose-600">*</span></label>
                        <input id="reference" name="reference" type="text" required value="{{ old('reference') }}" placeholder="SR-2026-000412"
                               class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm tabular ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                        @error('reference')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">Email Address You Used <span class="text-rose-600">*</span></label>
                        <input id="email" name="email" type="email" required value="{{ old('email') }}"
                               class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                        @error('email')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-brand-700 px-5 py-3.5 text-base font-semibold text-white transition hover:bg-brand-800">
                        Look Up My Request
                    </button>
                </div>

                <p class="section-divider mt-6 pt-5 text-sm text-slate-500">
                    Reported anonymously, or lost your reference? Use the tracking link from the confirmation
                    page, or <a href="{{ route('site.contact') }}" class="font-medium text-brand-700 hover:underline">contact us</a>.
                </p>
            </form>
        </div>
    </x-site.section>
</x-layouts.public>
