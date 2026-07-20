<x-layouts.public title="Search">
    <x-site.page-hero title="Search This Site"
                      subtitle="Find pages, documents, news, notices, meetings, and departments."
                      :crumbs="[['label' => 'Search']]" />

    <x-site.section :divider="false">
        <form method="GET" class="mx-auto mb-10 max-w-2xl">
            <label for="q" class="sr-only">Search Terms</label>
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 w-5 h-5 -translate-y-1/2 text-slate-400" />
                <input id="q" name="q" type="search" value="{{ $term }}" autofocus
                       placeholder="Try an ordinance number, a department, or a service"
                       class="block w-full rounded-xl border-0 py-4 pl-12 pr-32 text-base ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                    Search
                </button>
            </div>
        </form>

        @if ($term === '')
            <x-site.empty title="Enter A Search Term" icon="search"
                          message="Search across every published page, document, notice, and meeting record on this site." />
        @elseif ($total === 0)
            <x-site.empty title="No Results For That Search" icon="search"
                          message="Try fewer or more general words. If you are looking for a record we have not published, contact the Clerk's office." />
        @else
            <p class="mb-8 text-center text-slate-600">
                Found <span class="font-semibold text-slate-900 tabular">{{ $total }}</span> result(s) for
                <span class="font-semibold text-slate-900">{{ $term }}</span>.
            </p>

            <x-tabs :tabs="[
                'pages' => ['label' => 'Pages', 'icon' => 'book', 'count' => $results['pages']->count()],
                'documents' => ['label' => 'Documents', 'icon' => 'archive', 'count' => $results['documents']->count()],
                'news' => ['label' => 'News', 'icon' => 'bell', 'count' => $results['news']->count()],
                'notices' => ['label' => 'Notices', 'icon' => 'warning', 'count' => $results['notices']->count()],
                'meetings' => ['label' => 'Meetings', 'icon' => 'clock', 'count' => $results['meetings']->count()],
                'departments' => ['label' => 'Departments', 'icon' => 'building', 'count' => $results['departments']->count()],
            ]">
                <x-tab-panel name="pages">
                    @if ($results['pages']->count())
                        <ul class="space-y-3">
                            @foreach ($results['pages'] as $page)
                                <li class="rounded-xl bg-white p-5 ring-1 ring-slate-200">
                                    <a href="{{ route('site.page', $page->slug) }}" class="font-semibold text-slate-900 hover:text-brand-700 hover:underline">{{ $page->title }}</a>
                                    @if ($page->summary)<p class="mt-1 text-sm text-slate-600">{{ $page->summary }}</p>@endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <x-site.empty title="No Matching Pages" icon="book" />
                    @endif
                </x-tab-panel>

                <x-tab-panel name="documents">
                    @if ($results['documents']->count())
                        <ul class="space-y-3">
                            @foreach ($results['documents'] as $document)
                                <li class="flex items-center justify-between gap-4 rounded-xl bg-white p-5 ring-1 ring-slate-200">
                                    <div class="min-w-0">
                                        <a href="{{ route('site.files.show', $document->slug) }}" class="font-semibold text-slate-900 hover:text-brand-700 hover:underline">{{ $document->title }}</a>
                                        <p class="mt-0.5 text-sm text-slate-500">{{ $document->reference }} {{ $document->document_date?->format(config('municipal.date_format')) }}</p>
                                    </div>
                                    <a href="{{ route('site.files.download', $document->slug) }}" class="shrink-0 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline">
                                        <x-icon name="download" class="w-4 h-4" /> Download
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <x-site.empty title="No Matching Documents" icon="archive" />
                    @endif
                </x-tab-panel>

                <x-tab-panel name="news">
                    @if ($results['news']->count())
                        <ul class="space-y-3">
                            @foreach ($results['news'] as $post)
                                <li class="rounded-xl bg-white p-5 ring-1 ring-slate-200">
                                    <a href="{{ route('site.news.show', $post->slug) }}" class="font-semibold text-slate-900 hover:text-brand-700 hover:underline">{{ $post->title }}</a>
                                    <p class="mt-1 text-sm text-slate-600">{{ $post->teaser(140) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <x-site.empty title="No Matching News" icon="bell" />
                    @endif
                </x-tab-panel>

                <x-tab-panel name="notices">
                    @if ($results['notices']->count())
                        <ul class="space-y-3">
                            @foreach ($results['notices'] as $notice)
                                <li class="rounded-xl bg-white p-5 ring-1 ring-slate-200">
                                    <a href="{{ route('site.notices.show', $notice->slug) }}" class="font-semibold text-slate-900 hover:text-brand-700 hover:underline">{{ $notice->title }}</a>
                                    <p class="mt-0.5 text-sm text-slate-500">{{ $notice->notice_type }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <x-site.empty title="No Matching Notices" icon="warning" />
                    @endif
                </x-tab-panel>

                <x-tab-panel name="meetings">
                    @if ($results['meetings']->count())
                        <ul class="space-y-3">
                            @foreach ($results['meetings'] as $meeting)
                                <li class="rounded-xl bg-white p-5 ring-1 ring-slate-200">
                                    <a href="{{ route('site.meetings.show', $meeting->slug) }}" class="font-semibold text-slate-900 hover:text-brand-700 hover:underline">{{ $meeting->displayTitle() }}</a>
                                    <p class="mt-0.5 text-sm text-slate-500">{{ $meeting->meets_at->format(config('municipal.date_format')) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <x-site.empty title="No Matching Meetings" icon="clock" />
                    @endif
                </x-tab-panel>

                <x-tab-panel name="departments">
                    @if ($results['departments']->count())
                        <ul class="space-y-3">
                            @foreach ($results['departments'] as $department)
                                <li class="rounded-xl bg-white p-5 ring-1 ring-slate-200">
                                    <a href="{{ route('site.departments.show', $department->slug) }}" class="font-semibold text-slate-900 hover:text-brand-700 hover:underline">{{ $department->name }}</a>
                                    @if ($department->summary)<p class="mt-1 text-sm text-slate-600">{{ $department->summary }}</p>@endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <x-site.empty title="No Matching Departments" icon="building" />
                    @endif
                </x-tab-panel>
            </x-tabs>
        @endif
    </x-site.section>
</x-layouts.public>
