<x-layouts.public title="Arrest Records"
                  description="Recent bookings reported by the police department. An arrest is not a conviction.">
    <x-site.page-hero title="Arrest Records"
                      :eyebrow="$agencyName"
                      subtitle="Recent bookings, the charges filed at the time of booking, and the current disposition of each case."
                      icon="shield"
                      :crumbs="[['label' => 'Arrest Records']]" />

    <x-site.section :divider="false">
        <x-site.records-disclaimer :disclaimer="$disclaimer"
                                   :takedownContact="$takedownContact"
                                   :retentionDays="$retentionDays" />

        @if ($intro)
            <div class="mt-8 prose-civic max-w-3xl">
                <p>{{ $intro }}</p>
            </div>
        @endif
    </x-site.section>

    <x-site.section title="Booking Blotter" subtitle="Newest bookings first."
                    :href="$rosterEnabled ? route('site.records.roster') : null"
                    linkLabel="View The Inmate Roster">

        {{-- Filters. A plain GET form so results are linkable and work without JS. --}}
        <form method="GET" role="search" aria-label="Filter Arrest Records"
              class="rounded-2xl bg-slate-50 p-5 ring-1 ring-inset ring-slate-200">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
                <div class="space-y-1.5">
                    <label for="range" class="block text-sm font-medium text-slate-700">Date Range</label>
                    <x-select id="range" name="range">
                        @foreach ($ranges as $key => $label)
                            <option value="{{ $key }}" @selected($range === (string) $key)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </div>

                @if ($searchEnabled)
                    <div class="min-w-0 space-y-1.5 lg:col-span-2">
                        <label for="q" class="block text-sm font-medium text-slate-700">Search By Name, Case Number, Or Agency</label>
                        <x-input id="q" name="q" type="search" :value="$search" placeholder="Search Arrest Records…" />
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    <x-button type="submit" icon="filter">Apply Filters</x-button>
                    @if ($search || $range !== '30')
                        <x-button variant="secondary" :href="route('site.records.blotter')">Reset</x-button>
                    @endif
                </div>
            </div>
        </form>

        <div class="mt-8">
            @if ($records->count())
                <p class="mb-5 text-sm text-slate-500" role="status">
                    Showing {{ $records->count() }} of {{ $records->total() }} published record(s).
                </p>

                <ul class="space-y-4">
                    @foreach ($records as $record)
                        <li class="rounded-2xl bg-white p-5 sm:p-6 ring-1 ring-slate-200 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-5">
                                <div class="flex min-w-0 items-start gap-4">
                                    @if ($record->showsMugshot())
                                        <img src="{{ municipal_upload_url($record->mugshot_path) }}"
                                             alt="Booking photograph of {{ $record->fullName() }}"
                                             class="h-20 w-16 shrink-0 rounded-lg object-cover ring-1 ring-slate-200">
                                    @endif
                                    <div class="min-w-0">
                                        <h3 class="font-display text-lg font-semibold text-slate-900">
                                            <a href="{{ route('site.records.show', $record->public_ref) }}"
                                               class="rounded hover:text-brand-700 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                                                {{ $record->fullName() }}
                                            </a>
                                        </h3>
                                        <p class="mt-1 text-sm text-slate-500">
                                            @if ($record->age) Age {{ $record->age }} at booking &middot; @endif
                                            Booked {{ $record->booked_at->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}
                                        </p>
                                        <p class="mt-0.5 text-sm text-slate-500">{{ $record->arresting_agency }}</p>

                                        @if ($record->charges->count())
                                            <ul class="mt-3 flex flex-wrap gap-2">
                                                @foreach ($record->charges as $charge)
                                                    <li>
                                                        <x-badge :color="$charge->severityColor()">
                                                            {{ $charge->description }}@if ($charge->counts > 1) ({{ $charge->counts }} counts)@endif
                                                        </x-badge>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-col items-start gap-2 sm:items-end">
                                    <x-badge :color="$record->dispositionColor()" dot>{{ $record->dispositionLabel() }}</x-badge>
                                    <x-badge :color="$record->custodyColor()">{{ $record->custodyLabel() }}</x-badge>
                                    @if ($showCaseNumber && $record->case_number)
                                        <span class="text-xs font-medium text-slate-400">Case {{ $record->case_number }}</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-8">{{ $records->links() }}</div>
            @else
                <x-site.empty title="No Records Match This Filter" icon="shield"
                              message="Try widening the date range. Records are removed from this page once their retention period ends." />
            @endif
        </div>
    </x-site.section>
</x-layouts.public>
