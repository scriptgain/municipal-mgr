<x-layouts.app title="Dashboard">
    <x-page-header title="Staff Dashboard" icon="dashboard"
                   subtitle="What needs attention, what goes public next, and what residents just sent in.">
        <x-slot:actions>
            <x-button variant="secondary" icon="globe" href="{{ route('site.home') }}" target="_blank" rel="noopener">View Public Site</x-button>
            <x-button icon="plus" href="{{ route('news.create') }}">Post An Announcement</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($liveAlert)
        <div class="mb-6 rounded-xl bg-amber-50 px-5 py-4 ring-1 ring-amber-200">
            <div class="flex items-start gap-3">
                <x-icon name="bolt" class="mt-0.5 w-5 h-5 shrink-0 text-amber-600" />
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-amber-900">An Alert Banner Is Live On The Public Site</p>
                    <p class="mt-0.5 text-sm text-amber-800">{{ $liveAlert->title }}</p>
                </div>
                <x-button variant="secondary" size="sm" :href="route('alerts.edit', $liveAlert)">Manage Alert</x-button>
            </div>
        </div>
    @endif

    {{-- Attention row --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Open Service Requests" :value="$stats['open_requests']" icon="bolt"
                :href="route('service-requests.index')" />
        <x-stat label="Unassigned Requests" :value="$stats['unassigned_requests']" icon="warning"
                :href="route('service-requests.index')" />
        <x-stat label="Open Over A Week" :value="$stats['overdue_requests']" icon="clock"
                :href="route('service-requests.index')" />
        <x-stat label="Unread Form Submissions" :value="$stats['unread_submissions']" icon="clipboard"
                :href="route('forms.index')" />
    </div>

    <div class="section-divider my-8"></div>

    {{-- Tabs keep the dashboard to one screen instead of a long scroll. --}}
    <x-tabs :tabs="[
        'attention' => ['label' => 'Needs Attention', 'icon' => 'warning'],
        'requests' => ['label' => 'Recent Requests', 'icon' => 'bolt', 'count' => $recentRequests->count()],
        'upcoming' => ['label' => 'Coming Up', 'icon' => 'clock'],
        'content' => ['label' => 'Content Overview', 'icon' => 'book'],
    ]">
        <x-tab-panel name="attention">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-card title="Postings About To Lapse">
                    <ul class="divide-y divide-slate-100 text-sm">
                        <li class="flex items-center justify-between gap-3 py-3">
                            <span class="text-slate-600">Notices Expiring Within 7 Days</span>
                            <a href="{{ route('notices.index') }}" class="font-semibold text-brand-700 tabular hover:underline">{{ $attention['expiring_notices'] }}</a>
                        </li>
                        <li class="flex items-center justify-between gap-3 py-3">
                            <span class="text-slate-600">Bids Closing Within 7 Days</span>
                            <a href="{{ route('bids.index') }}" class="font-semibold text-brand-700 tabular hover:underline">{{ $attention['closing_bids'] }}</a>
                        </li>
                        <li class="flex items-center justify-between gap-3 py-3">
                            <span class="text-slate-600">Job Postings Closing Within 7 Days</span>
                            <a href="{{ route('jobs.index') }}" class="font-semibold text-brand-700 tabular hover:underline">{{ $attention['closing_jobs'] }}</a>
                        </li>
                    </ul>
                </x-card>

                <x-card title="Public Records Gaps">
                    <div class="flex items-start gap-3 rounded-lg bg-slate-50 px-4 py-3">
                        <x-icon name="clock" class="mt-0.5 w-5 h-5 shrink-0 text-slate-400" />
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900">
                                {{ $attention['meetings_missing_minutes'] }} Recent Meeting(s) Have No Minutes Posted
                            </p>
                            <p class="mt-1 text-sm text-slate-500">
                                Minutes are a public record. Posting them promptly is the single easiest
                                transparency win a municipality has.
                            </p>
                            <a href="{{ route('meetings.index') }}" class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline">
                                Review Meetings <x-icon name="chevron-right" class="w-4 h-4" />
                            </a>
                        </div>
                    </div>
                </x-card>
            </div>
        </x-tab-panel>

        <x-tab-panel name="requests">
            <x-card title="Recently Reported Issues" flush>
                @if ($recentRequests->count())
                    <x-table flush>
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Category</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Received</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentRequests as $request)
                                <tr>
                                    <td class="font-medium text-slate-900 tabular">{{ $request->reference }}</td>
                                    <td>{{ $request->category }}</td>
                                    <td>{{ $request->department?->name ?? 'Unassigned' }}</td>
                                    <td><x-badge :color="$request->statusColor()" dot>{{ $request->statusLabel() }}</x-badge></td>
                                    <td class="text-slate-500">{{ $request->created_at->diffForHumans() }}</td>
                                    <td class="text-right">
                                        <x-button size="sm" variant="secondary" :href="route('service-requests.show', $request)">Open</x-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @else
                    <x-admin.empty title="No Requests Yet" icon="bolt"
                                   message="Reports submitted from the public Report An Issue form appear here." />
                @endif
            </x-card>
        </x-tab-panel>

        <x-tab-panel name="upcoming">
            <div class="grid gap-4 lg:grid-cols-2">
                <x-card title="Upcoming Meetings" flush>
                    @if ($upcomingMeetings->count())
                        <ul class="divide-y divide-slate-100">
                            @foreach ($upcomingMeetings as $meeting)
                                <li class="flex items-center justify-between gap-4 px-5 py-3.5">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-slate-900">{{ $meeting->displayTitle() }}</p>
                                        <p class="text-xs text-slate-500">{{ $meeting->meets_at->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}</p>
                                    </div>
                                    @if ($meeting->agenda)
                                        <x-badge color="success" class="shrink-0">Agenda Posted</x-badge>
                                    @else
                                        <x-badge color="warn" class="shrink-0">No Agenda</x-badge>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <x-admin.empty title="No Meetings Scheduled" icon="clock" :href="route('meetings.create')" label="Schedule A Meeting" />
                    @endif
                </x-card>

                <x-card title="Upcoming Events" flush>
                    @if ($upcomingEvents->count())
                        <ul class="divide-y divide-slate-100">
                            @foreach ($upcomingEvents as $event)
                                <li class="px-5 py-3.5">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $event->title }}</p>
                                    <p class="text-xs text-slate-500">{{ $event->whenDisplay() }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <x-admin.empty title="No Events Scheduled" icon="clock" :href="route('events.create')" label="Add An Event" />
                    @endif
                </x-card>
            </div>
        </x-tab-panel>

        <x-tab-panel name="content">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat label="Published Pages" :value="$content['pages']" icon="book" :href="route('pages.index')" />
                <x-stat label="Draft Pages" :value="$content['drafts']" icon="edit" :href="route('pages.index')" />
                <x-stat label="Published Documents" :value="$content['documents']" icon="archive" :href="route('files.index')" />
                <x-stat label="Published News Posts" :value="$content['news']" icon="bell" :href="route('news.index')" />
            </div>

            <div class="mt-6">
                <x-card title="Service Request Volume" subtitle="Reports received over the last fourteen days.">
                    <div class="flex items-end gap-1.5" role="img" aria-label="Bar chart of service requests received over the last fourteen days">
                        @foreach ($activity as $day)
                            <div class="flex-1" data-tip="{{ $day['label'] }}: {{ $day['total'] }} received, {{ $day['resolved'] }} resolved">
                                <div class="mx-auto w-full rounded-t bg-brand-500/80"
                                     style="height: {{ max(4, $day['total'] * 12) }}px"></div>
                                <p class="mt-1.5 text-center text-[10px] text-slate-400">{{ $day['label'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            </div>
        </x-tab-panel>
    </x-tabs>
</x-layouts.app>
