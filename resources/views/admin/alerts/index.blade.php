<x-admin.index-shell title="Alerts Banner" icon="bolt"
    subtitle="The site-wide banner. The highest-severity live alert is the one residents see."
    :records="$records" :search="$search" placeholder="Search Alerts…"
    :createHref="route('alerts.create')" createLabel="Create Alert"
    :bulkAction="route('alerts.bulk-destroy')" label="Alert"
    emptyTitle="No Alerts Configured"
    emptyMessage="Use alerts for boil-water notices, storm closures, and service interruptions.">
    <x-table flush>
        <thead>
            <tr>
                <th class="w-10"><x-select-all /></th>
                <th>Alert</th>
                <th>Level</th>
                <th>Window</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $alert)
                <tr>
                    <td><x-select-row :id="$alert->id" :label="$alert->title" /></td>
                    <td>
                        <span class="block font-medium text-slate-900 truncate">{{ $alert->title }}</span>
                        <span class="block text-xs text-slate-400 truncate">{{ $alert->message }}</span>
                    </td>
                    <td>
                        @php($tone = ['emergency' => 'danger', 'warning' => 'warn', 'advisory' => 'info', 'info' => 'neutral'][$alert->level] ?? 'neutral')
                        <x-badge :color="$tone" dot>{{ \Illuminate\Support\Str::headline($alert->level) }}</x-badge>
                    </td>
                    <td class="text-slate-500">
                        {{ $alert->starts_at?->format(config('municipal.date_format')) ?? 'Now' }}
                        to
                        {{ $alert->ends_at?->format(config('municipal.date_format')) ?? 'Until Turned Off' }}
                    </td>
                    <td>
                        @if ($alert->is_active)
                            <x-status-dot color="success" pulse label="Live" />
                        @else
                            <x-status-dot color="neutral" label="Off" />
                        @endif
                    </td>
                    <td class="text-right">
                        <x-admin.row-actions :name="'del-alert-' . $alert->id"
                            :edit="route('alerts.edit', $alert)"
                            :delete="route('alerts.destroy', $alert)"
                            title="Delete This Alert?" />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-admin.index-shell>
