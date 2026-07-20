<x-layouts.public :title="$department->name" :description="$department->summary">
    <x-site.page-hero :title="$department->name" :subtitle="$department->summary" eyebrow="Department"
                      :crumbs="[['label' => 'Departments', 'href' => route('site.departments')], ['label' => $department->name]]" />

    <x-site.section :divider="false">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <x-tabs :tabs="[
                    'about' => ['label' => 'About', 'icon' => 'building'],
                    'staff' => ['label' => 'Staff', 'icon' => 'users', 'count' => $department->staff->count()],
                    'documents' => ['label' => 'Documents', 'icon' => 'archive', 'count' => $documents->count()],
                    'news' => ['label' => 'News And Events', 'icon' => 'bell'],
                ]">
                    <x-tab-panel name="about">
                        <div class="prose-civic">{!! $department->description !!}</div>

                        @if ($pages->count())
                            <div class="section-divider mt-8 pt-6">
                                <h2 class="font-display text-xl font-semibold text-slate-900">Pages In This Department</h2>
                                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                                    @foreach ($pages as $page)
                                        <li>
                                            <a href="{{ route('site.page', $page->slug) }}"
                                               class="flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-brand-700 ring-1 ring-slate-200 transition hover:bg-brand-50">
                                                <x-icon name="file-text" class="w-4 h-4 shrink-0 text-slate-400" /> {{ $page->title }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </x-tab-panel>

                    <x-tab-panel name="staff">
                        @if ($department->staff->count())
                            <ul class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 divide-y divide-slate-100">
                                @foreach ($department->staff as $member)
                                    <li class="flex flex-wrap items-center gap-4 p-5">
                                        <x-avatar :initials="$member->initials()" :name="$member->name"
                                                  :src="$member->photo_path ? municipal_upload_url($member->photo_path) : null" />
                                        <div class="min-w-0 flex-1">
                                            <p class="font-semibold text-slate-900">{{ $member->name }}</p>
                                            <p class="text-sm text-slate-500">{{ $member->job_title }}</p>
                                        </div>
                                        <div class="text-sm text-right">
                                            @if ($member->email)
                                                <a href="mailto:{{ $member->email }}" class="block text-brand-700 hover:underline">{{ $member->email }}</a>
                                            @endif
                                            @if ($member->phoneDisplay())
                                                <span class="block text-slate-500 tabular">{{ $member->phoneDisplay() }}</span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <x-site.empty title="No Staff Listed" icon="users" />
                        @endif
                    </x-tab-panel>

                    <x-tab-panel name="documents">
                        @if ($documents->count())
                            <ul class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 divide-y divide-slate-100">
                                @foreach ($documents as $document)
                                    <li class="flex items-center justify-between gap-4 p-5">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span class="inline-flex h-9 shrink-0 items-center rounded-md bg-slate-100 px-2 text-[11px] font-bold text-slate-600 ring-1 ring-slate-200">{{ $document->extension() }}</span>
                                            <a href="{{ route('site.files.show', $document->slug) }}" class="truncate font-medium text-slate-900 hover:text-brand-700 hover:underline">{{ $document->title }}</a>
                                        </div>
                                        <a href="{{ route('site.files.download', $document->slug) }}"
                                           class="shrink-0 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline">
                                            <x-icon name="download" class="w-4 h-4" /> Download
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="mt-6">
                                <a href="{{ route('site.files', ['department' => $department->slug]) }}" class="inline-flex items-center gap-1.5 font-semibold text-brand-700 hover:underline">
                                    All Documents From This Department <x-icon name="chevron-right" class="w-4 h-4" />
                                </a>
                            </div>
                        @else
                            <x-site.empty title="No Documents Published" icon="archive" />
                        @endif
                    </x-tab-panel>

                    <x-tab-panel name="news">
                        <div class="space-y-8">
                            @if ($news->count())
                                <div>
                                    <h3 class="font-display text-lg font-semibold text-slate-900">Recent News</h3>
                                    <ul class="mt-4 space-y-3">
                                        @foreach ($news as $post)
                                            <li class="rounded-xl bg-white p-4 ring-1 ring-slate-200">
                                                <a href="{{ route('site.news.show', $post->slug) }}" class="font-medium text-slate-900 hover:text-brand-700 hover:underline">{{ $post->title }}</a>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ $post->published_at?->format(config('municipal.date_format')) }}</p>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($events->count())
                                <div>
                                    <h3 class="font-display text-lg font-semibold text-slate-900">Upcoming Events</h3>
                                    <ul class="mt-4 space-y-3">
                                        @foreach ($events as $event)
                                            <li class="rounded-xl bg-white p-4 ring-1 ring-slate-200">
                                                <a href="{{ route('site.events.show', $event->slug) }}" class="font-medium text-slate-900 hover:text-brand-700 hover:underline">{{ $event->title }}</a>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ $event->whenDisplay() }}</p>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (! $news->count() && ! $events->count())
                                <x-site.empty title="Nothing Posted Recently" icon="bell" />
                            @endif
                        </div>
                    </x-tab-panel>
                </x-tabs>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">Contact This Department</h2>
                    <span class="seal-rule mt-3 mb-4"></span>
                    <dl class="space-y-3 text-sm">
                        @if ($department->head)
                            <div>
                                <dt class="font-medium text-slate-500">Department Head</dt>
                                <dd class="text-slate-900">{{ $department->head->name }} – {{ $department->head->job_title }}</dd>
                            </div>
                        @endif
                        @if ($department->phone)
                            <div>
                                <dt class="font-medium text-slate-500">Phone</dt>
                                <dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $department->phone) }}" class="text-brand-700 hover:underline">{{ $department->phone }}</a></dd>
                            </div>
                        @endif
                        @if ($department->fax)
                            <div>
                                <dt class="font-medium text-slate-500">Fax</dt>
                                <dd class="text-slate-900 tabular">{{ $department->fax }}</dd>
                            </div>
                        @endif
                        @if ($department->email)
                            <div>
                                <dt class="font-medium text-slate-500">Email</dt>
                                <dd><a href="mailto:{{ $department->email }}" class="break-all text-brand-700 hover:underline">{{ $department->email }}</a></dd>
                            </div>
                        @endif
                        @if ($department->address)
                            <div>
                                <dt class="font-medium text-slate-500">Address</dt>
                                <dd class="text-slate-900">{{ $department->address }}</dd>
                            </div>
                        @endif
                        @if ($department->hours)
                            <div>
                                <dt class="font-medium text-slate-500">Hours</dt>
                                <dd class="text-slate-900">{{ $department->hours }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                @if ($jobs->count())
                    <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                        <h2 class="font-semibold text-slate-900">Open Positions</h2>
                        <span class="seal-rule mt-3 mb-4"></span>
                        <ul class="space-y-3 text-sm">
                            @foreach ($jobs as $job)
                                <li>
                                    <a href="{{ route('site.jobs.show', $job->slug) }}" class="font-medium text-brand-700 hover:underline">{{ $job->title }}</a>
                                    <p class="text-xs text-slate-500">{{ $job->employment_type }} &middot; Closes {{ $job->closesDisplay() }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </x-site.section>
</x-layouts.public>
