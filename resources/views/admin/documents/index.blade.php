<x-admin.index-shell title="Document Library" icon="archive"
    subtitle="Ordinances, budgets, minutes, permits, and forms."
    :records="$records" :search="$search" placeholder="Search Documents…"
    :createHref="route('documents.create')" createLabel="Upload Document"
    :bulkAction="route('documents.bulk-destroy')" label="Document"
    emptyTitle="No Documents Uploaded"
    emptyMessage="The document library is what residents search when they want the real record.">
    <x-slot:actions>
        <x-button variant="secondary" icon="folder" :href="route('document-categories.index')">Categories</x-button>
    </x-slot:actions>

    <x-table flush>
        <thead>
            <tr>
                <th class="w-10"><x-select-all /></th>
                <th>Document</th>
                <th>Category</th>
                <th>Date</th>
                <th>Size</th>
                <th>Downloads</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $document)
                <tr>
                    <td><x-select-row :id="$document->id" :label="$document->title" /></td>
                    <td>
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="inline-flex h-8 shrink-0 items-center rounded-md bg-slate-100 px-2 text-[11px] font-bold text-slate-600 ring-1 ring-slate-200">{{ $document->extension() }}</span>
                            <span class="min-w-0">
                                <span class="block font-medium text-slate-900 truncate">{{ $document->title }}</span>
                                @if ($document->reference)<span class="block text-xs text-slate-400">{{ $document->reference }}</span>@endif
                            </span>
                        </div>
                    </td>
                    <td>{{ $document->category?->name ?? '—' }}</td>
                    <td class="text-slate-500">{{ $document->document_date?->format(config('municipal.date_format')) ?? '—' }}</td>
                    <td class="text-slate-500">{{ $document->sizeDisplay() }}</td>
                    <td class="tabular text-slate-500">{{ number_format($document->download_count) }}</td>
                    <td class="text-right">
                        <x-admin.row-actions :name="'del-doc-' . $document->id"
                            :edit="route('documents.edit', $document)"
                            :delete="route('documents.destroy', $document)"
                            :preview="route('site.documents.show', $document->slug)"
                            title="Delete This Document?"
                            message="The file is removed from the server. Meetings and notices linking to it will lose the attachment." />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-admin.index-shell>
