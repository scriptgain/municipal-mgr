<x-admin.index-shell title="Forms Builder" icon="edit"
    subtitle="Build a public form without a developer. Submissions land in an inbox here."
    :records="$records" :search="$search" placeholder="Search Forms…"
    :createHref="route('forms.create')" createLabel="Build A Form"
    :bulkAction="route('forms.bulk-destroy')" label="Form"
    emptyTitle="No Forms Built Yet"
    emptyMessage="Dog licence renewals, records requests, park reservations – anything with fields.">
    <x-table flush>
        <thead>
            <tr>
                <th class="w-10"><x-select-all /></th>
                <th>Form</th>
                <th>Department</th>
                <th>Fields</th>
                <th>Submissions</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $form)
                <tr>
                    <td><x-select-row :id="$form->id" :label="$form->name" /></td>
                    <td>
                        <span class="block font-medium text-slate-900 truncate">{{ $form->name }}</span>
                        <span class="block text-xs text-slate-400">/forms/{{ $form->slug }}</span>
                    </td>
                    <td>{{ $form->department?->name ?? '–' }}</td>
                    <td class="tabular text-slate-500">{{ count($form->fieldList()) }}</td>
                    <td class="tabular text-slate-500">{{ $form->submissions()->count() }}</td>
                    <td>
                        @if ($form->is_published)
                            <x-badge color="success" dot>Live</x-badge>
                        @else
                            <x-badge color="neutral" dot>Hidden</x-badge>
                        @endif
                    </td>
                    <td class="text-right">
                        <x-admin.row-actions :name="'del-form-' . $form->id"
                            :edit="route('forms.edit', $form)"
                            :delete="route('forms.destroy', $form)"
                            :preview="route('site.forms.show', $form->slug)"
                            title="Delete This Form?"
                            message="Every stored submission for this form is deleted with it.">
                            <a href="{{ route('forms.submissions.index', $form) }}" data-tip="View Submissions" aria-label="View Submissions"
                               class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-brand-700 transition">
                                <x-icon name="clipboard" class="w-4 h-4" />
                            </a>
                        </x-admin.row-actions>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-admin.index-shell>
