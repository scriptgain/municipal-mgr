<x-layouts.app :title="$form->name . ' Submissions'">
    <x-page-header :title="$form->name" icon="clipboard" subtitle="Submissions received from the public site.">
        <x-slot:actions>
            <x-button variant="secondary" icon="chevron-left" :href="route('forms.index')">Back To Forms</x-button>
            <x-button variant="secondary" icon="download" :href="route('forms.submissions.export', $form)">Export CSV</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card flush>
        <div x-data="{{ bulk_state($records->pluck('id')) }}">
            <x-bulk-bar :action="route('submissions.bulk-destroy')" label="Submission" modal="bulk-delete-submissions" />

            @if ($records->count())
                <x-table flush>
                    <thead>
                        <tr>
                            <th class="w-10"><x-select-all /></th>
                            <th>Received</th>
                            @foreach ($columns as $column)
                                <th>{{ $column['label'] }}</th>
                            @endforeach
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $submission)
                            <tr>
                                <td><x-select-row :id="$submission->id" label="Submission" /></td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        @if ($submission->isUnread())
                                            <x-status-dot color="info" pulse />
                                        @endif
                                        <span class="text-slate-600">{{ $submission->created_at->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}</span>
                                    </div>
                                </td>
                                @foreach ($columns as $column)
                                    <td>{{ \Illuminate\Support\Str::limit((string) ($submission->data[$column['key']] ?? '–'), 40) }}</td>
                                @endforeach
                                <td class="text-right">
                                    <x-admin.row-actions :name="'del-sub-' . $submission->id"
                                        :edit="route('submissions.show', $submission)"
                                        :delete="route('submissions.destroy', $submission)"
                                        title="Delete This Submission?" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @else
                <x-admin.empty title="No Submissions Yet" icon="clipboard"
                               message="Submissions from the public form will appear here as they arrive." />
            @endif
        </div>

        @if ($records->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $records->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
