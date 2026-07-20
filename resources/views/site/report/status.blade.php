<x-layouts.public :title="'Request ' . $record->reference">
    <x-site.page-hero :title="'Request ' . $record->reference" :eyebrow="$record->category"
                      :crumbs="[['label' => 'Track A Request', 'href' => route('site.track')], ['label' => $record->reference]]" />

    <x-site.section :divider="false">
        <div class="mx-auto grid max-w-4xl gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="font-display text-xl font-semibold text-slate-900">Current Status</h2>
                        <x-badge :color="$record->statusColor()" dot class="text-sm">{{ $record->statusLabel() }}</x-badge>
                    </div>
                    <span class="seal-rule mt-4"></span>
                    <p class="mt-5 whitespace-pre-line text-slate-700">{{ $record->description }}</p>

                    @if ($record->photo_path)
                        <img src="{{ municipal_upload_url($record->photo_path) }}" alt="Photograph submitted with this report"
                             class="mt-5 max-h-80 rounded-xl object-cover ring-1 ring-slate-200">
                    @endif
                </div>

                <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm">
                    <h2 class="font-display text-xl font-semibold text-slate-900">Progress</h2>
                    <span class="seal-rule mt-4 mb-6"></span>

                    <ol class="space-y-6">
                        @forelse ($record->publicUpdates as $update)
                            <li class="flex gap-4">
                                <span class="mt-1 inline-flex h-3 w-3 shrink-0 rounded-full bg-brand-500 ring-4 ring-brand-200"></span>
                                <div class="min-w-0">
                                    <p class="text-sm text-slate-800">{{ $update->note }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">
                                        {{ $update->created_at->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}
                                    </p>
                                </div>
                            </li>
                        @empty
                            <li class="text-sm text-slate-500">No updates have been posted yet.</li>
                        @endforelse
                    </ol>
                </div>
            </div>

            <aside>
                <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">Request Details</h2>
                    <span class="seal-rule mt-3 mb-4"></span>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="font-medium text-slate-500">Reference</dt>
                            <dd class="font-mono text-slate-900 tabular">{{ $record->reference }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Reported</dt>
                            <dd class="text-slate-900">{{ $record->created_at->format(config('municipal.date_format')) }}</dd>
                        </div>
                        @if ($record->location_text)
                            <div>
                                <dt class="font-medium text-slate-500">Location</dt>
                                <dd class="text-slate-900">{{ $record->location_text }}</dd>
                            </div>
                        @endif
                        @if ($record->department)
                            <div>
                                <dt class="font-medium text-slate-500">Handled By</dt>
                                <dd class="text-slate-900">{{ $record->department->name }}</dd>
                            </div>
                        @endif
                        @if ($record->resolved_at)
                            <div>
                                <dt class="font-medium text-slate-500">Resolved</dt>
                                <dd class="text-slate-900">{{ $record->resolved_at->format(config('municipal.date_format')) }}</dd>
                            </div>
                        @endif
                    </dl>

                    <a href="{{ route('site.report') }}"
                       class="mt-6 flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-800">
                        Report Another Issue
                    </a>
                </div>
            </aside>
        </div>
    </x-site.section>
</x-layouts.public>
