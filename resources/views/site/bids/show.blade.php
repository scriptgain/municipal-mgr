<x-layouts.public :title="$bid->title">
    <x-site.page-hero :title="$bid->title" :eyebrow="$bid->bid_type . ($bid->reference ? ' · ' . $bid->reference : '')"
                      :crumbs="[['label' => 'Bids And RFPs', 'href' => route('site.bids')], ['label' => $bid->title]]" />

    <x-site.section :divider="false">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                @if ($bid->isClosed())
                    <div class="mb-6 rounded-xl bg-slate-100 px-5 py-4 text-sm text-slate-700 ring-1 ring-slate-200">
                        This opportunity is closed.
                        @if ($bid->awarded_to) It was awarded to {{ $bid->awarded_to }}. @endif
                    </div>
                @endif

                <h2 class="font-display text-2xl font-semibold text-slate-900">Scope Of Work</h2>
                <span class="seal-rule mt-3 mb-5"></span>
                <div class="prose-civic">{!! $bid->description !!}</div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">Key Dates</h2>
                    <span class="seal-rule mt-3 mb-4"></span>
                    <dl class="space-y-3 text-sm">
                        @if ($bid->opens_at)
                            <div>
                                <dt class="font-medium text-slate-500">Issued</dt>
                                <dd class="text-slate-900">{{ $bid->opens_at->format(config('municipal.date_format')) }}</dd>
                            </div>
                        @endif
                        @if ($bid->pre_bid_meeting_at)
                            <div>
                                <dt class="font-medium text-slate-500">Pre-Bid Meeting</dt>
                                <dd class="text-slate-900">{{ $bid->pre_bid_meeting_at->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="font-medium text-slate-500">Submissions Close</dt>
                            <dd class="font-semibold text-slate-900">{{ $bid->closes_at?->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) ?? 'Open' }}</dd>
                        </div>
                    </dl>

                    @if ($bid->document)
                        <a href="{{ route('site.files.download', $bid->document->slug) }}"
                           class="mt-6 flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-800">
                            <x-icon name="download" class="w-4 h-4" /> Download The Bid Package
                        </a>
                    @endif
                </div>

                @if ($bid->contact_name || $bid->contact_email)
                    <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                        <h2 class="font-semibold text-slate-900">Questions</h2>
                        <span class="seal-rule mt-3 mb-4"></span>
                        <p class="text-sm text-slate-700">
                            {{ $bid->contact_name }}
                            @if ($bid->contact_email)
                                <a href="mailto:{{ $bid->contact_email }}" class="mt-1 block break-all text-brand-700 hover:underline">{{ $bid->contact_email }}</a>
                            @endif
                        </p>
                    </div>
                @endif
            </aside>
        </div>
    </x-site.section>
</x-layouts.public>
