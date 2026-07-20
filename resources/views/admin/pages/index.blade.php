<x-layouts.app title="Pages">
    <x-page-header title="Pages" icon="book" subtitle="Every page on the public website.">
        <x-slot:actions>
            <x-button href="{{ route('pages.create') }}" icon="plus">Add Page</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card flush>
        <div x-data="{{ bulk_state($records->pluck('id')) }}">
            <x-admin.index-toolbar :search="$search" placeholder="Search Pages…" :createHref="route('pages.create')" createLabel="Add Page" />
            <x-bulk-bar :action="route('pages.bulk-destroy')" label="Page" modal="bulk-delete-pages" />

            @if ($records->count())
                <x-table flush>
                    <thead>
                        <tr>
                            <th class="w-10"><x-select-all /></th>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $page)
                            <tr>
                                <td><x-select-row :id="$page->id" :label="$page->title" /></td>
                                <td class="font-medium text-slate-900">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="truncate">{{ $page->title }}</span>
                                        @if ($page->parent)
                                            <span class="shrink-0 text-xs text-slate-400" data-tip="Child of {{ $page->parent->title }}">
                                                <x-icon name="chevron-right" class="w-3.5 h-3.5" />
                                            </span>
                                        @endif
                                    </div>
                                    <span class="block text-xs text-slate-400">/pages/{{ $page->slug }}</span>
                                </td>
                                <td>{{ $page->department?->name ?? '—' }}</td>
                                <td>
                                    @if ($page->status === 'published')
                                        <x-badge color="success" dot>Published</x-badge>
                                    @else
                                        <x-badge color="neutral" dot>Draft</x-badge>
                                    @endif
                                </td>
                                <td class="text-slate-500">{{ $page->updated_at?->format(config('municipal.date_format')) }}</td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('site.page', $page->slug) }}" target="_blank" rel="noopener" data-tip="Preview On The Public Site"
                                           class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-brand-700 transition">
                                            <x-icon name="eye" class="w-4 h-4" />
                                        </a>
                                        <form method="POST" action="{{ route('pages.duplicate', $page) }}">
                                            @csrf
                                            <button type="submit" data-tip="Duplicate As Draft" aria-label="Duplicate Page"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-brand-700 transition">
                                                <x-icon name="copy" class="w-4 h-4" />
                                            </button>
                                        </form>
                                        <a href="{{ route('pages.edit', $page) }}" data-tip="Edit Page"
                                           class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-brand-700 transition">
                                            <x-icon name="edit" class="w-4 h-4" />
                                        </a>
                                        <x-delete-button :name="'del-page-' . $page->id" :action="route('pages.destroy', $page)"
                                                         title="Delete This Page?"
                                                         :message="'Deleting &quot;' . $page->title . '&quot; removes it from the public site permanently.'" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @else
                <x-admin.empty title="No Pages Yet" icon="book"
                               message="Pages are the backbone of the site: departments, services, how-to guides, and policies."
                               :href="route('pages.create')" label="Create The First Page" />
            @endif
        </div>

        @if ($records->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $records->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
