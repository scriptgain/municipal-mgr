<x-layouts.public title="Events">
    <x-site.page-hero title="Community Events"
                      subtitle="Programs, celebrations, closures, and recreation across the community."
                      :crumbs="[['label' => 'Events']]" />

    <x-site.section :divider="false">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
            <div class="flex gap-2">
                <a href="{{ route('site.events') }}"
                   @class(['rounded-lg px-4 py-2.5 text-sm font-semibold transition',
                           'bg-brand-700 text-white' => ! $showingPast,
                           'bg-white text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50' => $showingPast])>Upcoming</a>
                <a href="{{ route('site.events', ['when' => 'past']) }}"
                   @class(['rounded-lg px-4 py-2.5 text-sm font-semibold transition',
                           'bg-brand-700 text-white' => $showingPast,
                           'bg-white text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50' => ! $showingPast])>Past Events</a>
            </div>
            <a href="{{ route('site.calendar') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-slate-300 transition hover:bg-brand-50">
                <x-icon name="calendar" class="w-4 h-4" /> Month View
            </a>
        </div>

        @if ($events->count())
            <ul class="space-y-4">
                @foreach ($events as $event)
                    <li class="flex flex-wrap gap-5 rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm">
                        <div class="flex h-20 w-20 shrink-0 flex-col items-center justify-center rounded-xl bg-brand-50 text-brand-800 ring-1 ring-brand-100">
                            <span class="text-xs font-semibold uppercase">{{ $event->starts_at->format('M') }}</span>
                            <span class="text-2xl font-bold leading-none tabular">{{ $event->starts_at->format('j') }}</span>
                            <span class="text-[11px] text-brand-500">{{ $event->starts_at->format('D') }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ $event->category }}</span>
                                @if ($event->department)
                                    <span class="text-xs text-slate-400">{{ $event->department->name }}</span>
                                @endif
                            </div>
                            <h2 class="mt-2 text-lg font-semibold text-slate-900">
                                <a href="{{ route('site.events.show', $event->slug) }}" class="hover:text-brand-700 hover:underline">{{ $event->title }}</a>
                            </h2>
                            <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                                <span class="inline-flex items-center gap-1.5"><x-icon name="clock" class="w-4 h-4 text-slate-400" /> {{ $event->whenDisplay() }}</span>
                                @if ($event->location)
                                    <span class="inline-flex items-center gap-1.5"><x-icon name="map-pin" class="w-4 h-4 text-slate-400" /> {{ $event->location }}</span>
                                @endif
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-10">{{ $events->links() }}</div>
        @else
            <x-site.empty title="No Events To Show" icon="calendar"
                          message="Community events will be posted here as they are scheduled." />
        @endif
    </x-site.section>
</x-layouts.public>
