<x-admin.index-shell title="Elected Officials" icon="shield"
    subtitle="Mayor, council members, and appointed officers, with their terms of office."
    :records="$records" :search="$search" placeholder="Search Officials…"
    :createHref="route('officials.create')" createLabel="Add Official"
    :bulkAction="route('officials.bulk-destroy')" label="Elected Official"
    emptyTitle="No Officials Listed"
    emptyMessage="Residents look for the council roster more than almost anything else on a municipal site.">
    <x-table flush>
        <thead>
            <tr>
                <th class="w-10"><x-select-all /></th>
                <th>Name</th>
                <th>Office</th>
                <th>District</th>
                <th>Term</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $official)
                <tr>
                    <td><x-select-row :id="$official->id" :label="$official->name" /></td>
                    <td>
                        <div class="flex items-center gap-3 min-w-0">
                            <x-avatar size="sm" :initials="$official->initials()" :name="$official->name"
                                      :src="$official->photo_path ? municipal_upload_url($official->photo_path) : null" />
                            <span class="font-medium text-slate-900 truncate">{{ $official->name }}</span>
                        </div>
                    </td>
                    <td>{{ $official->office }}</td>
                    <td>{{ $official->district ?: '–' }}</td>
                    <td class="text-slate-500">{{ $official->termDisplay() ?: '–' }}</td>
                    <td>
                        @if ($official->is_current)
                            <x-badge color="success" dot>In Office</x-badge>
                        @else
                            <x-badge color="neutral" dot>Former</x-badge>
                        @endif
                    </td>
                    <td class="text-right">
                        <x-admin.row-actions :name="'del-official-' . $official->id"
                            :edit="route('officials.edit', $official)"
                            :delete="route('officials.destroy', $official)"
                            title="Remove This Official?"
                            message="Consider marking them as a former official instead – the historical roster is a public record." />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-admin.index-shell>
