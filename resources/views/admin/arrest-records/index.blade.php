<x-layouts.app title="Arrest Records">
    <x-page-header title="Arrest Records" icon="shield"
                   subtitle="Booking records, their disposition, and what is visible on the public blotter.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" :href="route('settings.records.edit')">Module Settings</x-button>
            <x-button :href="route('arrest-records.create')" icon="plus">Add A Booking Record</x-button>
        </x-slot:actions>
    </x-page-header>

    @unless ($mugshotsEnabled)
        <x-alert type="info" title="Mugshots Are Not Published" class="mb-5">
            Booking photographs can be stored and viewed by staff, but they are hidden from the public site.
            Change this under Module Settings only if publication is lawful in your jurisdiction.
        </x-alert>
    @endunless

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <x-stat label="Total Records" :value="$counts['total']" icon="archive" />
        <x-stat label="Currently In Custody" :value="$counts['in_custody']" icon="lock" />
        <x-stat label="Published To The Blotter" :value="$counts['published']" icon="globe" />
        <x-stat label="Past Retention" :value="$counts['lapsed']" icon="clock"
                :trend="$counts['lapsed'] ? 'Awaiting auto-unpublish' : null" trendColor="neutral" />
    </div>

    <x-card flush>
        <div x-data="{{ bulk_state($records->pluck('id')) }}">
            {{-- Filters --}}
            <form method="GET" class="border-b border-slate-200 bg-white px-4 py-4">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6 lg:items-end">
                    <div class="min-w-0 space-y-1.5 lg:col-span-2">
                        <label for="q" class="block text-sm font-medium text-slate-700">Search</label>
                        <x-input id="q" name="q" type="search" :value="$search" placeholder="Name, Case Number, Agency…" />
                    </div>

                    <div class="space-y-1.5">
                        <label for="state" class="block text-sm font-medium text-slate-700">Visibility</label>
                        <x-select id="state" name="state">
                            <option value="">All Records</option>
                            <option value="published" @selected($filters['state'] === 'published')>Published</option>
                            <option value="unpublished" @selected($filters['state'] === 'unpublished')>Not Published</option>
                            <option value="expired" @selected($filters['state'] === 'expired')>Past Retention</option>
                            <option value="juvenile" @selected($filters['state'] === 'juvenile')>Juvenile (Blocked)</option>
                        </x-select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="custody" class="block text-sm font-medium text-slate-700">Custody</label>
                        <x-select id="custody" name="custody">
                            <option value="">Any Status</option>
                            @foreach ($custodyStatuses as $key => $status)
                                <option value="{{ $key }}" @selected($filters['custody'] === $key)>{{ $status['label'] }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="disposition" class="block text-sm font-medium text-slate-700">Disposition</label>
                        <x-select id="disposition" name="disposition">
                            <option value="">Any Disposition</option>
                            @foreach ($dispositions as $key => $option)
                                <option value="{{ $key }}" @selected($filters['disposition'] === $key)>{{ $option['label'] }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-button type="submit" variant="secondary" size="sm" icon="filter">Filter</x-button>
                        <x-button variant="ghost" size="sm" :href="route('arrest-records.index')">Reset</x-button>
                    </div>

                    <div class="space-y-1.5">
                        <label for="from" class="block text-sm font-medium text-slate-700">Booked From</label>
                        <x-input id="from" name="from" type="date" :value="$filters['from']" />
                    </div>

                    <div class="space-y-1.5">
                        <label for="to" class="block text-sm font-medium text-slate-700">Booked To</label>
                        <x-input id="to" name="to" type="date" :value="$filters['to']" />
                    </div>
                </div>
            </form>

            <x-bulk-bar :action="route('arrest-records.bulk-destroy')" label="Arrest Record" modal="bulk-delete-arrest-records" />

            @if ($records->count())
                <x-table flush>
                    <thead>
                        <tr>
                            <th class="w-10"><x-select-all /></th>
                            <th>Subject</th>
                            <th>Booked</th>
                            <th>Charges</th>
                            <th>Custody</th>
                            <th>Disposition</th>
                            <th>Public</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $record)
                            <tr>
                                <td><x-select-row :id="$record->id" :label="$record->reference()" /></td>
                                <td>
                                    <span class="block font-medium text-slate-900 truncate">{{ $record->listName() }}</span>
                                    <span class="block text-xs text-slate-400">
                                        {{ $record->age ? 'Age ' . $record->age : 'Age not recorded' }}
                                        @if ($record->case_number) &middot; {{ $record->case_number }} @endif
                                    </span>
                                </td>
                                <td class="text-slate-500">{{ $record->booked_at?->format(config('municipal.date_format')) ?? ': ' }}</td>
                                <td class="text-slate-500">{{ $record->charges->count() }}</td>
                                <td><x-badge :color="$record->custodyColor()" dot>{{ $record->custodyLabel() }}</x-badge></td>
                                <td><x-badge :color="$record->dispositionColor()" dot>{{ $record->dispositionLabel() }}</x-badge></td>
                                <td>
                                    @if ($record->isJuvenile())
                                        <x-badge color="danger" dot>Juvenile, Blocked</x-badge>
                                    @elseif ($record->retentionExpired())
                                        <x-badge color="neutral" dot>Retention Ended</x-badge>
                                    @elseif ($record->is_published)
                                        <x-badge color="success" dot>Published</x-badge>
                                    @else
                                        <x-badge color="neutral" dot>Not Published</x-badge>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($record->isPubliclyVisible())
                                            <a href="{{ route('site.records.show', $record->public_ref) }}" target="_blank" rel="noopener"
                                               data-tip="Preview On The Public Site" aria-label="Preview"
                                               class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-brand-700">
                                                <x-icon name="eye" class="w-4 h-4" aria-hidden="true" />
                                            </a>
                                        @endif

                                        <a href="{{ route('arrest-records.edit', $record->public_ref) }}" data-tip="Edit" aria-label="Edit"
                                           class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-brand-700">
                                            <x-icon name="edit" class="w-4 h-4" aria-hidden="true" />
                                        </a>

                                        @if ($record->is_published)
                                            <x-confirm-action :name="'unpublish-' . $record->id"
                                                              :action="route('arrest-records.unpublish', $record->public_ref)"
                                                              method="PUT" tone="warn"
                                                              title="Remove From The Public Blotter?"
                                                              message="The record stays available to staff. It disappears from the public blotter and the inmate roster immediately."
                                                              confirm="Unpublish" confirmVariant="danger">
                                                <button type="button" data-tip="Unpublish" aria-label="Unpublish"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-amber-700">
                                                    <x-icon name="lock" class="w-4 h-4" aria-hidden="true" />
                                                </button>
                                            </x-confirm-action>
                                        @elseif (! $record->isJuvenile())
                                            <x-confirm-action :name="'publish-' . $record->id"
                                                              :action="route('arrest-records.publish', $record->public_ref)"
                                                              method="PUT"
                                                              title="Publish To The Public Blotter?"
                                                              message="This makes the subject's name, charges, and disposition publicly visible until the retention window ends. Confirm the disposition is current before publishing."
                                                              confirm="Publish" confirmIcon="globe">
                                                <button type="button" data-tip="Publish" aria-label="Publish"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-emerald-700">
                                                    <x-icon name="globe" class="w-4 h-4" aria-hidden="true" />
                                                </button>
                                            </x-confirm-action>
                                        @endif

                                        <x-records.expunge-button :name="'expunge-row-' . $record->id"
                                                                  :action="route('arrest-records.expunge', $record->public_ref)"
                                                                  :reference="$record->reference()" />

                                        <x-delete-button :name="'del-record-' . $record->id"
                                                         :action="route('arrest-records.destroy', $record->public_ref)"
                                                         title="Delete This Record?"
                                                         message="Use this when a record was entered in error. If a court has ordered the record sealed or expunged, use Expunge instead so the removal is logged as compliance with the order." />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @else
                <x-admin.empty title="No Records Match" icon="shield"
                               message="Nothing matches the current filters. Booking records added here are unpublished until someone publishes them."
                               :href="route('arrest-records.create')" label="Add A Booking Record" />
            @endif
        </div>

        @if ($records->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $records->links() }}</div>
        @endif
    </x-card>

    <p class="mt-4 text-sm text-slate-500">
        Published records are removed from the public blotter {{ $retentionDays }} days after the booking date.
    </p>
</x-layouts.app>
