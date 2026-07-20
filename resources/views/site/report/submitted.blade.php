<x-layouts.public title="Report Submitted">
    <x-site.page-hero title="Thank You. Your Report Has Been Received."
                      :eyebrow="'Reference ' . $record->reference"
                      :crumbs="[['label' => 'Report An Issue', 'href' => route('site.report')], ['label' => 'Submitted']]" />

    <x-site.section :divider="false">
        <div class="mx-auto max-w-2xl">
            <div class="rounded-2xl bg-white p-8 text-center ring-1 ring-slate-200 shadow-sm">
                <span class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200">
                    <x-icon name="check-circle" class="w-8 h-8" />
                </span>
                <h2 class="mt-5 font-display text-2xl font-semibold text-slate-900">Your Reference Number</h2>
                <p class="mt-3 font-mono text-3xl font-bold tracking-tight text-brand-700 tabular">{{ $record->reference }}</p>

                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <button type="button" data-copy="{{ $record->reference }}"
                            class="rounded-lg bg-white px-5 py-3 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50">
                        Copy Reference
                    </button>
                    <a href="{{ route('site.report.status', $record->tracking_token) }}"
                       class="rounded-lg bg-brand-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-800">
                        Track This Request
                    </a>
                </div>

                <p class="section-divider mt-8 pt-6 text-sm leading-relaxed text-slate-600">
                    Bookmark the tracking link above — it is the fastest way to check progress.
                    @if ($record->reporter_email)
                        You can also look this up later using your reference number and the email address you gave us.
                    @endif
                </p>
            </div>

            <div class="mt-6 text-center">
                <a href="{{ route('site.home') }}" class="text-sm font-semibold text-brand-700 hover:underline">Return To The Homepage</a>
            </div>
        </div>
    </x-site.section>
</x-layouts.public>
