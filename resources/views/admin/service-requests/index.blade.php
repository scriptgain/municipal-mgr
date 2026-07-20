<x-layouts.app title="Service Requests">
    <x-page-header title="Service Requests" icon="bolt"
                   subtitle="Issues residents reported from the public site.">
    </x-page-header>

    <x-card flush>
        <div x-data="{{ bulk_state($records->pluck('id')) }}">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <label class="sr-only" for="q">Search Requests</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 w-4 h-4 -translate-y-1/2 text-slate-400" />
                        <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Reference, address, or description…"
                               class="w-72 rounded-lg border-0 py-2 pl-9 pr-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                    </div>

                    <label class="sr-only" for="status">Status</label>
                    <select id="status" name="status" data-auto-submit
                            class="rounded-lg border-0 py-2 pl-3 pr-9 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                        <option value="open" @selected($status === 'open')>All Open</option>
                        @foreach ($statuses as $key => $meta)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>

                    <label class="sr-only" for="department">Department</label>
                    <select id="department" name="department" data-auto-submit
                            class="rounded-lg border-0 py-2 pl-3 pr-9 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                        <option value="">All Departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(request('department') == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>

                    <x-button type="submit" variant="secondary" size="sm">Filter</x-button>
                </form>

                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-medium tabular">{{ $counts['open'] }} Open</span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-medium tabular">{{ $counts['new'] }} New</span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-medium tabular">{{ $counts['in_progress'] }} In Progress</span>
                </div>
            </div>

            <x-bulk-bar :action="route('service-requests.bulk-destroy')" label="Service Request" modal="bulk-delete-requests" />

            @if ($records->count())
                <x-table flush>
                    <thead>
                        <tr>
                            <th class="w-10"><x-select-all /></th>
                            <th>Reference</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Age</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $request)
                            <tr>
                                <td><x-select-row :id="$request->id" :label="$request->reference" /></td>
                                <td class="font-medium text-slate-900 tabular">{{ $request->reference }}</td>
                                <td>{{ $request->category }}</td>
                                <td class="text-slate-600">{{ $request->location_text ?: '—' }}</td>
                                <td>{{ $request->department?->name ?? 'Unassigned' }}</td>
                                <td><x-badge :color="$request->statusColor()" dot>{{ $request->statusLabel() }}</x-badge></td>
                                <td class="text-slate-500">{{ $request->created_at->diffForHumans(null, true) }}</td>
                                <td class="text-right">
                                    <x-button size="sm" variant="secondary" :href="route('service-requests.show', $request)">Open</x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @else
                <x-admin.empty title="No Requests Match" icon="bolt"
                               message="Try clearing the filters, or wait for the next report from the public site." />
            @endif
        </div>

        @if ($records->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $records->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
