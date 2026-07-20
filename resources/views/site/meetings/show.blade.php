<x-layouts.public :title="$meeting->displayTitle()">
    <x-site.page-hero :title="$meeting->displayTitle()" :eyebrow="$meeting->body"
                      :subtitle="$meeting->meets_at->format(config('municipal.date_format') . ', ' . config('municipal.time_format'))"
                      :crumbs="[['label' => 'Meetings', 'href' => route('site.meetings')], ['label' => $meeting->displayTitle()]]" />

    <x-site.section :divider="false">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="min-w-0 lg:col-span-2">
                @if ($meeting->status === 'cancelled')
                    <div class="mb-6 rounded-xl bg-rose-50 px-5 py-4 font-semibold text-rose-800 ring-1 ring-rose-200">
                        This Meeting Has Been Cancelled.
                    </div>
                @endif

                <div class="prose-civic">{!! $meeting->summary !!}</div>

                <div class="section-divider mt-8 pt-6">
                    <h2 class="font-display text-xl font-semibold text-slate-900">Meeting Documents</h2>
                    <ul class="mt-4 space-y-3">
                        @foreach ([['Agenda', $meeting->agenda], ['Meeting Packet', $meeting->packet], ['Approved Minutes', $meeting->minutes]] as [$docLabel, $document])
                            <li class="flex items-center justify-between gap-4 rounded-xl bg-white p-4 ring-1 ring-slate-200">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 ring-1 ring-brand-200">
                                        <x-icon name="file-text" class="w-4 h-4" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-medium text-slate-900">{{ $docLabel }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ $document?->file_name ?? 'Not Posted Yet' }}</p>
                                    </div>
                                </div>
                                @if ($document)
                                    <a href="{{ route('site.files.download', $document->slug) }}"
                                       class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                                        <x-icon name="download" class="w-4 h-4" /> Download
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">Meeting Information</h2>
                    <span class="seal-rule mt-3 mb-4"></span>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="font-medium text-slate-500">Body</dt>
                            <dd class="text-slate-900">{{ $meeting->body }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Date And Time</dt>
                            <dd class="text-slate-900">{{ $meeting->meets_at->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}</dd>
                        </div>
                        @if ($meeting->location)
                            <div>
                                <dt class="font-medium text-slate-500">Location</dt>
                                <dd class="text-slate-900">
                                    {{ $meeting->location }}
                                    @if ($meeting->address)<span class="block text-slate-600">{{ $meeting->address }}</span>@endif
                                </dd>
                            </div>
                        @endif
                    </dl>

                    @if ($meeting->video_url)
                        <a href="{{ $meeting->video_url }}" target="_blank" rel="noopener"
                           class="mt-6 flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-800">
                            <x-icon name="play" class="w-4 h-4" /> Watch The Recording
                        </a>
                    @endif
                </div>

                @if ($related->count())
                    <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                        <h2 class="font-semibold text-slate-900">Other {{ $meeting->body }} Meetings</h2>
                        <span class="seal-rule mt-3 mb-4"></span>
                        <ul class="space-y-3 text-sm">
                            @foreach ($related as $item)
                                <li>
                                    <a href="{{ route('site.meetings.show', $item->slug) }}" class="text-brand-700 hover:underline">{{ $item->meets_at->format(config('municipal.date_format')) }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </x-site.section>
</x-layouts.public>
