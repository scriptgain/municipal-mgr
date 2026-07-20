<x-layouts.app title="Inmate Roster">
    <x-page-header title="Inmate Roster" icon="lock"
                   subtitle="Everyone whose custody status is In Custody, published or not.">
        <x-slot:actions>
            <x-button variant="secondary" icon="shield" :href="route('arrest-records.index')">All Arrest Records</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card flush>
        @if ($records->count())
            <x-table flush>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Age</th>
                        <th>Booked</th>
                        <th>Charges</th>
                        <th>Bond</th>
                        <th>Disposition</th>
                        <th>Public</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($records as $record)
                        <tr>
                            <td>
                                <span class="block font-medium text-slate-900 truncate">{{ $record->listName() }}</span>
                                @if ($record->case_number)
                                    <span class="block text-xs text-slate-400">{{ $record->case_number }}</span>
                                @endif
                            </td>
                            <td class="tabular text-slate-500">{{ $record->age ?: ': ' }}</td>
                            <td class="text-slate-500">{{ $record->booked_at?->format(config('municipal.date_format')) ?? ': ' }}</td>
                            <td class="text-slate-500">{{ $record->charges->count() }}</td>
                            <td class="tabular text-slate-500">
                                {{ $record->bond_amount !== null ? '$' . number_format((float) $record->bond_amount, 2) : ': ' }}
                            </td>
                            <td><x-badge :color="$record->dispositionColor()" dot>{{ $record->dispositionLabel() }}</x-badge></td>
                            <td>
                                @if ($record->isJuvenile())
                                    <x-badge color="danger" dot>Juvenile, Blocked</x-badge>
                                @elseif ($record->isPubliclyVisible())
                                    <x-badge color="success" dot>Published</x-badge>
                                @else
                                    <x-badge color="neutral" dot>Not Published</x-badge>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('arrest-records.edit', $record->public_ref) }}" data-tip="Edit" aria-label="Edit"
                                   class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-brand-700">
                                    <x-icon name="edit" class="w-4 h-4" aria-hidden="true" />
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @else
            <x-admin.empty title="Nobody Is In Custody" icon="lock"
                           message="Records whose custody status is In Custody appear here and, once published, on the public roster." />
        @endif

        @if ($records->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $records->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
