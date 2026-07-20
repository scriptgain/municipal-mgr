<x-admin.index-shell title="Navigation Menus" icon="globe"
    subtitle="The primary navbar, homepage quick links, top utility bar, and footer."
    :records="$records" :search="$search" placeholder="Search Menu Items…"
    :createHref="route('menus.create')" createLabel="Add Menu Item"
    :bulkAction="route('menus.bulk-destroy')" label="Menu Item"
    emptyTitle="No Menu Items"
    emptyMessage="Run php artisan municipal:bootstrap to seed a sensible default menu.">
    <x-table flush>
        <thead>
            <tr>
                <th class="w-10"><x-select-all /></th>
                <th>Label</th>
                <th>Menu</th>
                <th>Links To</th>
                <th>Order</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $item)
                <tr>
                    <td><x-select-row :id="$item->id" :label="$item->label" /></td>
                    <td>
                        <div class="flex items-center gap-2.5 min-w-0">
                            @if ($item->icon)
                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 ring-1 ring-brand-200">
                                    <x-icon :name="$item->icon" class="w-3.5 h-3.5" />
                                </span>
                            @endif
                            <span class="font-medium text-slate-900 truncate">{{ $item->label }}</span>
                        </div>
                    </td>
                    <td><x-badge color="info">{{ \Illuminate\Support\Str::headline($item->menu) }}</x-badge></td>
                    <td class="text-slate-500 truncate">{{ $item->page?->title ?? $item->url ?? '—' }}</td>
                    <td class="tabular text-slate-500">{{ $item->sort_order }}</td>
                    <td class="text-right">
                        <x-admin.row-actions :name="'del-menu-' . $item->id"
                            :edit="route('menus.edit', $item)"
                            :delete="route('menus.destroy', $item)"
                            title="Delete This Menu Item?" />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-admin.index-shell>
