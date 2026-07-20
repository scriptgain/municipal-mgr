<x-layouts.app title="Expungement Log">
    <x-page-header title="Expungement Log" icon="book"
                   subtitle="Proof that court-ordered removals were carried out.">
        <x-slot:actions>
            <x-button variant="secondary" icon="shield" :href="route('arrest-records.index')">All Arrest Records</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-alert type="info" title="Why There Are No Names Here" class="mb-6">
        These entries record that an order was executed, against which case, by whom, and when. They deliberately do not
        keep the subject's name: a compliance log that retains the name a court ordered erased has quietly defeated the
        order it exists to prove compliance with. Entries cannot be edited or deleted.
    </x-alert>

    <x-card flush>
        @if ($records->count())
            <x-table flush>
                <thead>
                    <tr>
                        <th>Carried Out</th>
                        <th>Case</th>
                        <th>Ordered By</th>
                        <th>Order Reference</th>
                        <th>Performed By</th>
                        <th>Photograph</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($records as $entry)
                        <tr>
                            <td class="text-slate-500">{{ $entry->performed_at?->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}</td>
                            <td class="font-medium text-slate-900">{{ $entry->case_number ?: $entry->booking_number ?: ': ' }}</td>
                            <td class="text-slate-700">{{ $entry->ordered_by ?: ': ' }}</td>
                            <td class="text-slate-500">{{ $entry->order_reference ?: ': ' }}</td>
                            <td class="text-slate-500">{{ $entry->performer?->name ?: ($entry->performed_by_name ?: 'Unknown') }}</td>
                            <td>
                                @if ($entry->mugshot_destroyed)
                                    <x-badge color="success" dot>Destroyed</x-badge>
                                @else
                                    <x-badge color="neutral" dot>None On File</x-badge>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @else
            <x-admin.empty title="No Expungements Recorded" icon="book"
                           message="When a court orders a record sealed or expunged, carrying out that order writes an entry here." />
        @endif

        @if ($records->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $records->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
