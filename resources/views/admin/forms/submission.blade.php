<x-layouts.app title="Submission">
    <x-page-header :title="$record->form?->name ?? 'Submission'" icon="clipboard"
                   :subtitle="'Received ' . $record->created_at->format(config('municipal.date_format') . ', ' . config('municipal.time_format'))">
        <x-slot:actions>
            @if ($record->form)
                <x-button variant="secondary" icon="chevron-left" :href="route('forms.submissions.index', $record->form)">Back To Submissions</x-button>
            @endif
            <x-delete-button :name="'del-submission-' . $record->id" :action="route('submissions.destroy', $record)"
                             title="Delete This Submission?" message="This permanently removes the resident's submission." />
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <dl class="divide-y divide-slate-100">
            @foreach ($fields as $field)
                <div class="grid gap-2 py-4 sm:grid-cols-3">
                    <dt class="text-sm font-medium text-slate-500">{{ $field['label'] }}</dt>
                    <dd class="text-sm text-slate-900 sm:col-span-2 whitespace-pre-line">{{ $record->data[$field['key']] ?? '—' }}</dd>
                </div>
            @endforeach
            <div class="grid gap-2 py-4 sm:grid-cols-3">
                <dt class="text-sm font-medium text-slate-500">Submitted From</dt>
                <dd class="text-sm text-slate-500 sm:col-span-2 tabular">{{ $record->ip ?: 'Unknown' }}</dd>
            </div>
        </dl>
    </x-card>
</x-layouts.app>
