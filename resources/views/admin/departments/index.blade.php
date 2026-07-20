<x-admin.index-shell title="Departments" icon="building"
    subtitle="The organisational spine: staff, documents, services, and requests all hang off a department."
    :records="$records" :search="$search" placeholder="Search Departments…"
    :createHref="route('departments.create')" createLabel="Add Department"
    :bulkAction="route('departments.bulk-destroy')" label="Department"
    emptyTitle="No Departments Yet"
    emptyMessage="Start with Administration, Public Works, Police, and Parks And Recreation.">
    <x-table flush>
        <thead>
            <tr>
                <th class="w-10"><x-select-all /></th>
                <th>Department</th>
                <th>Department Head</th>
                <th>Phone</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $department)
                <tr>
                    <td><x-select-row :id="$department->id" :label="$department->name" /></td>
                    <td>
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 ring-1 ring-brand-200">
                                <x-icon :name="$department->icon ?: 'building'" class="w-4 h-4" />
                            </span>
                            <span class="min-w-0">
                                <span class="block font-medium text-slate-900 truncate">{{ $department->name }}</span>
                                <span class="block text-xs text-slate-400 truncate">{{ $department->summary }}</span>
                            </span>
                        </div>
                    </td>
                    <td>{{ $department->head?->name ?? '–' }}</td>
                    <td class="tabular">{{ $department->phone ?: '–' }}</td>
                    <td>
                        @if ($department->is_published)
                            <x-badge color="success" dot>Published</x-badge>
                        @else
                            <x-badge color="neutral" dot>Hidden</x-badge>
                        @endif
                    </td>
                    <td class="text-right">
                        <x-admin.row-actions :name="'del-dept-' . $department->id"
                            :edit="route('departments.edit', $department)"
                            :delete="route('departments.destroy', $department)"
                            :preview="route('site.departments.show', $department->slug)"
                            title="Delete This Department?"
                            message="Staff, documents, and requests assigned to it will be left unassigned." />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-admin.index-shell>
