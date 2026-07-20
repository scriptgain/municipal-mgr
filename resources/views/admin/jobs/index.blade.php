<x-admin.index-shell title="Job Postings" icon="users"
    subtitle="Open positions with the municipality."
    :records="$records" :search="$search" placeholder="Search Postings…"
    :createHref="route('jobs.create')" createLabel="Post A Job"
    :bulkAction="route('jobs.bulk-destroy')" label="Job Posting"
    emptyTitle="No Job Postings"
    emptyMessage="Postings close automatically at their deadline unless marked open until filled.">
    <x-table flush>
        <thead>
            <tr>
                <th class="w-10"><x-select-all /></th>
                <th>Position</th>
                <th>Department</th>
                <th>Type</th>
                <th>Closes</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $job)
                <tr>
                    <td><x-select-row :id="$job->id" :label="$job->title" /></td>
                    <td class="font-medium text-slate-900">{{ $job->title }}</td>
                    <td>{{ $job->department?->name ?? '—' }}</td>
                    <td><x-badge color="info">{{ $job->employment_type }}</x-badge></td>
                    <td class="text-slate-500">{{ $job->closesDisplay() }}</td>
                    <td>
                        @if ($job->status === 'published')
                            <x-badge color="success" dot>Accepting</x-badge>
                        @elseif ($job->status === 'closed')
                            <x-badge color="neutral" dot>Closed</x-badge>
                        @else
                            <x-badge color="neutral" dot>Draft</x-badge>
                        @endif
                    </td>
                    <td class="text-right">
                        <x-admin.row-actions :name="'del-job-' . $job->id"
                            :edit="route('jobs.edit', $job)"
                            :delete="route('jobs.destroy', $job)"
                            :preview="route('site.jobs.show', $job->slug)"
                            title="Delete This Posting?" />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-admin.index-shell>
