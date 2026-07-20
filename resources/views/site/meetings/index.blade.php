<x-layouts.public title="Meetings, Agendas, And Minutes">
    <x-site.page-hero title="Meetings, Agendas, And Minutes"
                      subtitle="Every public meeting, with its agenda, packet, minutes, and recording."
                      :crumbs="[['label' => 'Meetings']]" />

    <x-site.section :divider="false">
        @if ($bodies->count() > 1)
            <form method="GET" class="mb-8 flex flex-wrap items-end gap-3 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                <div>
                    <label for="body" class="block text-sm font-medium text-slate-700">Public Body</label>
                    <select id="body" name="body" data-auto-submit
                            class="mt-1.5 rounded-lg border-0 py-2.5 pl-3 pr-9 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                        <option value="">All Bodies</option>
                        @foreach ($bodies as $body)
                            <option value="{{ $body }}" @selected($activeBody === $body)>{{ $body }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Filter</button>
            </form>
        @endif

        <x-tabs :tabs="[
            'upcoming' => ['label' => 'Upcoming', 'icon' => 'calendar', 'count' => $upcoming->count()],
            'past' => ['label' => 'Past Meetings', 'icon' => 'archive', 'count' => $past->total()],
        ]">
            <x-tab-panel name="upcoming">
                @if ($upcoming->count())
                    <ul class="space-y-4">
                        @foreach ($upcoming as $meeting)
                            <li class="flex flex-wrap gap-5 rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm">
                                <div class="flex h-20 w-20 shrink-0 flex-col items-center justify-center rounded-xl bg-brand-50 text-brand-800 ring-1 ring-brand-200">
                                    <span class="text-xs font-semibold uppercase">{{ $meeting->meets_at->format('M') }}</span>
                                    <span class="text-2xl font-bold leading-none tabular">{{ $meeting->meets_at->format('j') }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h2 class="text-lg font-semibold text-slate-900">
                                        <a href="{{ route('site.meetings.show', $meeting->slug) }}" class="hover:text-brand-700 hover:underline">{{ $meeting->displayTitle() }}</a>
                                    </h2>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $meeting->meets_at->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}
                                        @if ($meeting->location) &middot; {{ $meeting->location }} @endif
                                    </p>
                                    @if ($meeting->status === 'cancelled')
                                        <p class="mt-2 inline-flex rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">This Meeting Has Been Cancelled</p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-start gap-2">
                                    @if ($meeting->agenda)
                                        <a href="{{ route('site.files.download', $meeting->agenda->slug) }}"
                                           class="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-800 ring-1 ring-brand-200 transition hover:bg-brand-100">
                                            <x-icon name="download" class="w-4 h-4" /> Agenda
                                        </a>
                                    @endif
                                    @if ($meeting->packet)
                                        <a href="{{ route('site.files.download', $meeting->packet->slug) }}"
                                           class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 transition hover:bg-slate-50">
                                            <x-icon name="download" class="w-4 h-4" /> Packet
                                        </a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <x-site.empty title="No Upcoming Meetings Posted" icon="calendar"
                                  message="Meetings are posted here in advance, along with their agendas." />
                @endif
            </x-tab-panel>

            <x-tab-panel name="past">
                @if ($past->count())
                    <div class="overflow-x-auto mm-scroll rounded-2xl ring-1 ring-slate-200 bg-white">
                        <table class="w-full text-left text-sm">
                            <caption class="sr-only">Past meetings with agendas and minutes</caption>
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Meeting</th>
                                    <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                                    <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Documents</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($past as $meeting)
                                    <tr class="hover:bg-brand-50/40">
                                        <td class="px-5 py-4">
                                            <a href="{{ route('site.meetings.show', $meeting->slug) }}" class="font-medium text-slate-900 hover:text-brand-700 hover:underline">{{ $meeting->displayTitle() }}</a>
                                        </td>
                                        <td class="px-5 py-4 text-slate-600">{{ $meeting->meets_at->format(config('municipal.date_format')) }}</td>
                                        <td class="px-5 py-4">
                                            <div class="flex flex-wrap gap-3 text-sm">
                                                @if ($meeting->agenda)
                                                    <a href="{{ route('site.files.download', $meeting->agenda->slug) }}" class="inline-flex items-center gap-1 text-brand-700 hover:underline">
                                                        <x-icon name="download" class="w-3.5 h-3.5" /> Agenda
                                                    </a>
                                                @endif
                                                @if ($meeting->minutes)
                                                    <a href="{{ route('site.files.download', $meeting->minutes->slug) }}" class="inline-flex items-center gap-1 text-brand-700 hover:underline">
                                                        <x-icon name="download" class="w-3.5 h-3.5" /> Minutes
                                                    </a>
                                                @endif
                                                @if ($meeting->video_url)
                                                    <a href="{{ $meeting->video_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-brand-700 hover:underline">
                                                        <x-icon name="play" class="w-3.5 h-3.5" /> Video
                                                    </a>
                                                @endif
                                                @if (! $meeting->agenda && ! $meeting->minutes && ! $meeting->video_url)
                                                    <span class="text-slate-400">None Posted</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-8">{{ $past->links() }}</div>
                @else
                    <x-site.empty title="No Past Meetings On Record" icon="archive" />
                @endif
            </x-tab-panel>
        </x-tabs>
    </x-site.section>
</x-layouts.public>
