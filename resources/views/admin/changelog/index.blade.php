<x-admin.index-shell title="Changelog" icon="megaphone"
    subtitle="Dated release notes shown on the public What's New page."
    :records="$records" :search="$search" placeholder="Search Release Notes…"
    :createHref="route('changelog.create')" createLabel="Add Release Note"
    :bulkAction="route('changelog.bulk-destroy')" label="Changelog Entry"
    emptyTitle="No Release Notes Yet"
    emptyMessage="Release notes appear publicly on the What's New page, newest first.">
    <x-table flush>
        <thead>
            <tr>
                <th class="w-10"><x-select-all /></th>
                <th>Version</th>
                <th>Title</th>
                <th>Released</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $entry)
                <tr>
                    <td><x-select-row :id="$entry->id" :label="$entry->title" /></td>
                    <td>
                        <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 ring-1 ring-inset ring-brand-200">v{{ $entry->version }}</span>
                    </td>
                    <td class="font-medium text-slate-900">
                        <span class="block truncate">{{ $entry->title }}</span>
                        @if ($entry->summary)
                            <span class="block truncate text-xs font-normal text-slate-500">{{ $entry->summary }}</span>
                        @endif
                    </td>
                    <td class="text-slate-500">{{ $entry->released_on?->format(config('municipal.date_format')) ?? '–' }}</td>
                    <td>
                        @if ($entry->is_published)
                            <x-badge color="success" dot>Published</x-badge>
                        @else
                            <x-badge color="neutral" dot>Draft</x-badge>
                        @endif
                    </td>
                    <td class="text-right">
                        <x-admin.row-actions :name="'del-changelog-' . $entry->id"
                            :edit="route('changelog.edit', $entry)"
                            :delete="route('changelog.destroy', $entry)"
                            :preview="route('site.changelog')"
                            title="Delete This Release Note?" />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-admin.index-shell>
