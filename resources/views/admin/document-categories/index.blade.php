<x-admin.index-shell title="Document Categories" icon="folder"
    subtitle="How the public document library is organised."
    :records="$records" :search="$search" placeholder="Search Categories…"
    :createHref="route('document-categories.create')" createLabel="Add Category"
    :bulkAction="route('document-categories.bulk-destroy')" label="Document Category"
    emptyTitle="No Categories Yet"
    emptyMessage="Ordinances, Budgets, Agendas And Minutes, and Forms are a solid starting set.">
    <x-table flush>
        <thead>
            <tr>
                <th class="w-10"><x-select-all /></th>
                <th>Category</th>
                <th>Description</th>
                <th>Order</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $category)
                <tr>
                    <td><x-select-row :id="$category->id" :label="$category->name" /></td>
                    <td>
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 ring-1 ring-brand-200">
                                <x-icon :name="$category->icon ?: 'folder'" class="w-4 h-4" />
                            </span>
                            <span class="font-medium text-slate-900 truncate">{{ $category->name }}</span>
                        </div>
                    </td>
                    <td class="text-slate-500">{{ $category->description ?: '—' }}</td>
                    <td class="tabular text-slate-500">{{ $category->sort_order }}</td>
                    <td class="text-right">
                        <x-admin.row-actions :name="'del-cat-' . $category->id"
                            :edit="route('document-categories.edit', $category)"
                            :delete="route('document-categories.destroy', $category)"
                            title="Delete This Category?"
                            message="Documents in it become uncategorised; the files themselves are kept." />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-admin.index-shell>
