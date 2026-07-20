<x-admin.index-shell title="Public Notices" icon="warning"
    subtitle="Statutory postings: hearings, ordinances, elections, and legal notices."
    :records="$records" :search="$search" placeholder="Search Notices…"
    :createHref="route('notices.create')" createLabel="Post A Notice"
    :bulkAction="route('notices.bulk-destroy')" label="Public Notice"
    emptyTitle="No Notices Posted"
    emptyMessage="Public notices carry legal posting and expiry dates and appear in their own archive.">
    <x-table flush>
        <thead>
            <tr>
                <th class="w-10"><x-select-all /></th>
                <th>Notice</th>
                <th>Type</th>
                <th>Posted</th>
                <th>Expires</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $notice)
                <tr>
                    <td><x-select-row :id="$notice->id" :label="$notice->title" /></td>
                    <td class="font-medium text-slate-900">{{ $notice->title }}</td>
                    <td><x-badge color="info">{{ $notice->notice_type }}</x-badge></td>
                    <td class="text-slate-500">{{ $notice->posted_at?->format(config('municipal.date_format')) ?? '–' }}</td>
                    <td class="text-slate-500">{{ $notice->expires_at?->format(config('municipal.date_format')) ?? 'No Expiry' }}</td>
                    <td>
                        @if ($notice->status !== 'published')
                            <x-badge color="neutral" dot>Draft</x-badge>
                        @elseif ($notice->isExpired())
                            <x-badge color="warn" dot>Expired</x-badge>
                        @else
                            <x-badge color="success" dot>Posted</x-badge>
                        @endif
                    </td>
                    <td class="text-right">
                        <x-admin.row-actions :name="'del-notice-' . $notice->id"
                            :edit="route('notices.edit', $notice)"
                            :delete="route('notices.destroy', $notice)"
                            :preview="route('site.notices.show', $notice->slug)"
                            title="Delete This Notice?"
                            message="Public notices are records. Consider letting it expire instead of deleting it." />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-admin.index-shell>
