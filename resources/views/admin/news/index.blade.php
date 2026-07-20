<x-admin.index-shell title="News And Announcements" icon="bell"
    subtitle="Press releases, service alerts, and community announcements."
    :records="$records" :search="$search" placeholder="Search News…"
    :createHref="route('news.create')" createLabel="Add News Post"
    :bulkAction="route('news.bulk-destroy')" label="News Post"
    emptyTitle="No News Posts Yet"
    emptyMessage="Announcements appear on the homepage and in the news archive.">
    <x-table flush>
        <thead>
            <tr>
                <th class="w-10"><x-select-all /></th>
                <th>Headline</th>
                <th>Category</th>
                <th>Department</th>
                <th>Status</th>
                <th>Published</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $post)
                <tr>
                    <td><x-select-row :id="$post->id" :label="$post->title" /></td>
                    <td class="font-medium text-slate-900">
                        <div class="flex items-center gap-2 min-w-0">
                            @if ($post->is_featured)
                                <span class="shrink-0 text-seal-500" data-tip="Featured on the homepage"><x-icon name="star" class="w-4 h-4" /></span>
                            @endif
                            <span class="truncate">{{ $post->title }}</span>
                        </div>
                    </td>
                    <td><x-badge color="info">{{ $post->category }}</x-badge></td>
                    <td>{{ $post->department?->name ?? '–' }}</td>
                    <td>
                        @if ($post->status === 'published')
                            <x-badge color="success" dot>Published</x-badge>
                        @else
                            <x-badge color="neutral" dot>Draft</x-badge>
                        @endif
                    </td>
                    <td class="text-slate-500">{{ $post->published_at?->format(config('municipal.date_format')) ?? '–' }}</td>
                    <td class="text-right">
                        <x-admin.row-actions :name="'del-news-' . $post->id"
                            :edit="route('news.edit', $post)"
                            :delete="route('news.destroy', $post)"
                            :preview="route('site.news.show', $post->slug)"
                            title="Delete This News Post?" />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-admin.index-shell>
