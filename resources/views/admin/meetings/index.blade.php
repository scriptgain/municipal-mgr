<x-admin.index-shell title="Meetings" icon="clock"
    subtitle="Posted meetings with agendas, packets, minutes, and video."
    :records="$records" :search="$search" placeholder="Search Meetings…"
    :createHref="route('meetings.create')" createLabel="Schedule A Meeting"
    :bulkAction="route('meetings.bulk-destroy')" label="Meeting"
    emptyTitle="No Meetings Posted"
    emptyMessage="Open-meetings law makes this the most-visited section of most municipal sites.">
    <x-table flush>
        <thead>
            <tr>
                <th class="w-10"><x-select-all /></th>
                <th>Meeting</th>
                <th>Date And Time</th>
                <th>Agenda</th>
                <th>Minutes</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $meeting)
                <tr>
                    <td><x-select-row :id="$meeting->id" :label="$meeting->displayTitle()" /></td>
                    <td class="font-medium text-slate-900">{{ $meeting->displayTitle() }}</td>
                    <td class="text-slate-600">{{ $meeting->meets_at->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}</td>
                    <td>
                        @if ($meeting->agenda)
                            <x-badge color="success" dot>Posted</x-badge>
                        @else
                            <x-badge color="warn" dot>Missing</x-badge>
                        @endif
                    </td>
                    <td>
                        @if ($meeting->minutes)
                            <x-badge color="success" dot>Approved</x-badge>
                        @elseif ($meeting->meets_at->isPast())
                            <x-badge color="warn" dot>Pending</x-badge>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td>
                        @if ($meeting->status === 'cancelled')
                            <x-badge color="danger" dot>Cancelled</x-badge>
                        @elseif ($meeting->status === 'held')
                            <x-badge color="neutral" dot>Held</x-badge>
                        @else
                            <x-badge color="info" dot>Scheduled</x-badge>
                        @endif
                    </td>
                    <td class="text-right">
                        <x-admin.row-actions :name="'del-meeting-' . $meeting->id"
                            :edit="route('meetings.edit', $meeting)"
                            :delete="route('meetings.destroy', $meeting)"
                            :preview="route('site.meetings.show', $meeting->slug)"
                            title="Delete This Meeting?"
                            message="Agendas and minutes are public records. Deleting the meeting removes the public link to them." />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-admin.index-shell>
