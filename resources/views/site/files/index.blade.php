<x-layouts.public :title="$folder?->name ?? 'Files And Documents'">
    <x-site.page-hero :title="$folder?->name ?? 'Files And Documents'"
                      :subtitle="$folder?->description ?? 'Ordinances, budgets, minutes, permits, forms, maps, and public records.'"
                      :crumbs="$crumbs" />

    <x-site.section :divider="false">
        {{-- Search and filters. A real search landmark with real labels. --}}
        <form method="GET" role="search" aria-label="Search Files" class="mb-8 rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200">
            @if ($folder)<input type="hidden" name="folder" value="{{ $folder->slug }}">@endif
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[16rem]">
                    <label for="q" class="block text-sm font-medium text-slate-700">Search Files</label>
                    <div class="relative mt-1.5">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 w-4 h-4 -translate-y-1/2 text-slate-400" />
                        <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Ordinance number, title, or keyword"
                               class="block w-full rounded-lg border-0 py-2.5 pl-9 pr-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                    </div>
                </div>

                <div>
                    <label for="department" class="block text-sm font-medium text-slate-700">Department</label>
                    <select id="department" name="department" data-auto-submit
                            class="mt-1.5 rounded-lg border-0 py-2.5 pl-3 pr-9 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                        <option value="">All Departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->slug }}" @selected($activeDepartment === $department->slug)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="kind" class="block text-sm font-medium text-slate-700">File Type</label>
                    <select id="kind" name="kind" data-auto-submit
                            class="mt-1.5 rounded-lg border-0 py-2.5 pl-3 pr-9 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                        <option value="">All File Types</option>
                        <option value="document" @selected($activeKind === 'document')>Documents</option>
                        <option value="image" @selected($activeKind === 'image')>Images And Maps</option>
                    </select>
                </div>

                <button type="submit" class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">Search</button>
                @if ($search || $activeDepartment || $activeKind)
                    <a href="{{ route('site.files', $folder ? ['folder' => $folder->slug] : []) }}"
                       class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Clear</a>
                @endif
            </div>
        </form>

        {{-- Folder navigation. Residents browse by folder; each card is a plain
             link so it works with keyboard, screen readers, and no JavaScript. --}}
        @if ($childFolders->count())
            <nav aria-label="Browse By Folder">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Browse By Folder</h2>
                <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($childFolders as $child)
                        <li>
                            <a href="{{ route('site.files', ['folder' => $child->slug]) }}"
                               class="flex h-full items-start gap-3.5 rounded-2xl bg-white p-5 ring-1 ring-slate-200 transition hover:ring-brand-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-200">
                                    <x-icon :name="$child->icon ?: 'folder'" class="w-5 h-5" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block font-semibold text-slate-900">{{ $child->name }}</span>
                                    @if ($child->description)
                                        <span class="mt-0.5 block text-sm text-slate-500">{{ $child->description }}</span>
                                    @endif
                                    <span class="mt-1.5 block text-xs font-medium text-slate-400 tabular">{{ $folderCounts[$child->id] ?? 0 }} File(s)</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="section-divider my-10"></div>
        @endif

        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            @if ($search) Search Results @elseif ($folder) Files In {{ $folder->name }} @else All Files @endif
        </h2>

        @if ($files->count())
            <ul class="mt-4 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 divide-y divide-slate-100">
                @foreach ($files as $file)
                    <li class="flex flex-wrap items-center gap-4 p-5">
                        @if ($file->isImage())
                            <img src="{{ $file->url() }}" alt="{{ $file->alt_text }}"
                                 class="h-11 w-11 shrink-0 rounded-lg object-cover ring-1 ring-slate-200">
                        @else
                            <span class="inline-flex h-11 shrink-0 items-center rounded-lg bg-slate-100 px-3 text-xs font-bold text-slate-600 ring-1 ring-slate-200">{{ $file->extension() }}</span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-slate-900">
                                <a href="{{ route('site.files.show', $file) }}"
                                   class="rounded hover:text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">{{ $file->title }}</a>
                            </h3>
                            <p class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                                @if ($file->reference)<span class="font-medium text-slate-600">{{ $file->reference }}</span>@endif
                                @if ($file->folder)<span>{{ $file->folder->name }}</span>@endif
                                @if ($file->document_date)<span>{{ $file->document_date->format(config('municipal.date_format')) }}</span>@endif
                                <span>{{ $file->sizeDisplay() }}</span>
                            </p>
                        </div>

                        <a href="{{ route('site.files.download', $file) }}"
                           class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                            <x-icon name="download" class="w-4 h-4" />
                            Download
                            <span class="sr-only">{{ $file->title }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-10">{{ $files->links() }}</div>
        @else
            <div class="mt-4">
                <x-site.empty title="No Files Match Your Search" icon="archive"
                              message="Try a broader keyword, or browse by folder above. If you cannot find a record, contact the Clerk's office." />
            </div>
        @endif
    </x-site.section>
</x-layouts.public>
