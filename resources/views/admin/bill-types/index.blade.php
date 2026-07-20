<x-admin.index-shell title="Bill Types" icon="archive"
                     subtitle="What the town bills for, and how residents pay each one."
                     :records="$records"
                     :search="$search"
                     placeholder="Search bill types…"
                     :createHref="route('bill-types.create')"
                     createLabel="Add A Bill Type"
                     :bulkAction="route('bill-types.bulk-destroy')"
                     label="Bill Type"
                     emptyTitle="No Bill Types Yet"
                     emptyMessage="Add the things residents can pay for: a utility bill, a permit fee, a citation.">

    <x-table flush>
        <caption class="sr-only">Bill Types</caption>
        <thead>
            <tr>
                <th scope="col" class="w-12"><x-select-all /></th>
                <th scope="col">Type</th>
                <th scope="col">Department</th>
                <th scope="col">Lookup Required</th>
                <th scope="col">Pay Without A Bill</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $type)
                <tr>
                    <td><x-select-row :id="$type->id" :label="$type->label" /></td>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 ring-1 ring-brand-200">
                                <x-icon :name="$type->icon" class="w-4 h-4" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <a href="{{ route('bill-types.edit', $type) }}" class="block font-medium text-slate-900 hover:text-brand-700">{{ $type->label }}</a>
                                @if ($type->description)
                                    <span class="block truncate text-xs text-slate-500">{{ $type->description }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="text-slate-600">{{ $type->department?->name ?? 'Not Assigned' }}</td>
                    <td>
                        @if ($type->requires_lookup)
                            <x-badge color="info" dot>Reference Needed</x-badge>
                        @else
                            <x-badge color="neutral">Not Needed</x-badge>
                        @endif
                    </td>
                    <td>
                        @if ($type->allows_open_payment)
                            <x-badge color="success" dot>Allowed</x-badge>
                        @else
                            <x-badge color="neutral">Not Allowed</x-badge>
                        @endif
                    </td>
                    <td>
                        @if ($type->is_active)
                            <x-badge color="success" dot>Active</x-badge>
                        @else
                            <x-badge color="neutral" dot>Hidden</x-badge>
                        @endif
                    </td>
                    <td class="text-right">
                        <x-admin.row-actions :edit="route('bill-types.edit', $type)"
                                             :delete="route('bill-types.destroy', $type)"
                                             :name="'delete-bill-type-' . $type->id"
                                             title="Delete This Bill Type?"
                                             message="Bills already raised under this type are removed with it. If you only want to stop residents choosing it, switch it to hidden instead." />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-admin.index-shell>
