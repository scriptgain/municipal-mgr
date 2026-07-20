<x-admin.index-shell title="Bids And RFPs" icon="database"
    subtitle="Procurement opportunities. Closing times here are legally binding."
    :records="$records" :search="$search" placeholder="Search Bids…"
    :createHref="route('bids.create')" createLabel="Post An Opportunity"
    :bulkAction="route('bids.bulk-destroy')" label="Bid Or RFP"
    emptyTitle="No Open Opportunities"
    emptyMessage="Vendors check this page before they call the clerk.">
    <x-table flush>
        <thead>
            <tr>
                <th class="w-10"><x-select-all /></th>
                <th>Opportunity</th>
                <th>Type</th>
                <th>Closes</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $bid)
                <tr>
                    <td><x-select-row :id="$bid->id" :label="$bid->title" /></td>
                    <td>
                        <span class="block font-medium text-slate-900 truncate">{{ $bid->title }}</span>
                        @if ($bid->reference)<span class="block text-xs text-slate-400">{{ $bid->reference }}</span>@endif
                    </td>
                    <td><x-badge color="info">{{ $bid->bid_type }}</x-badge></td>
                    <td class="text-slate-500">{{ $bid->closes_at?->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) ?? '–' }}</td>
                    <td>
                        @if ($bid->status === 'open' && ! $bid->isClosed())
                            <x-badge color="success" dot>Open</x-badge>
                        @elseif ($bid->status === 'awarded')
                            <x-badge color="info" dot>Awarded</x-badge>
                        @elseif ($bid->status === 'cancelled')
                            <x-badge color="danger" dot>Cancelled</x-badge>
                        @else
                            <x-badge color="neutral" dot>Closed</x-badge>
                        @endif
                    </td>
                    <td class="text-right">
                        <x-admin.row-actions :name="'del-bid-' . $bid->id"
                            :edit="route('bids.edit', $bid)"
                            :delete="route('bids.destroy', $bid)"
                            :preview="route('site.bids.show', $bid->slug)"
                            title="Delete This Opportunity?" />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-admin.index-shell>
