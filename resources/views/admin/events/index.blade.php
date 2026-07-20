<x-admin.index-shell title="Events Calendar" icon="clock"
    subtitle="Community events, closures, and recreation programming."
    :records="$records" :search="$search" placeholder="Search Events…"
    :createHref="route('events.create')" createLabel="Add Event"
    :bulkAction="route('events.bulk-destroy')" label="Event"
    emptyTitle="No Events Scheduled"
    emptyMessage="Events appear on the public calendar and the homepage.">
    <x-table flush>
        <thead>
            <tr>
                <th class="w-10"><x-select-all /></th>
                <th>Event</th>
                <th>When</th>
                <th>Category</th>
                <th>Location</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $event)
                <tr>
                    <td><x-select-row :id="$event->id" :label="$event->title" /></td>
                    <td class="font-medium text-slate-900">{{ $event->title }}</td>
                    <td class="text-slate-600">{{ $event->whenDisplay() }}</td>
                    <td><x-badge color="info">{{ $event->category }}</x-badge></td>
                    <td>{{ $event->location ?: '—' }}</td>
                    <td>
                        @if ($event->is_published)
                            <x-badge color="success" dot>Published</x-badge>
                        @else
                            <x-badge color="neutral" dot>Hidden</x-badge>
                        @endif
                    </td>
                    <td class="text-right">
                        <x-admin.row-actions :name="'del-event-' . $event->id"
                            :edit="route('events.edit', $event)"
                            :delete="route('events.destroy', $event)"
                            :preview="route('site.events.show', $event->slug)"
                            title="Delete This Event?" />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-admin.index-shell>
