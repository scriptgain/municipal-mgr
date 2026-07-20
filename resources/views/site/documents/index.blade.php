<x-layouts.public title="Document Library">
    <x-site.page-hero title="Document Library"
                      subtitle="Ordinances, budgets, minutes, permits, forms, and public records."
                      :crumbs="[['label' => 'Documents']]" />

    <x-site.section :divider="false">
        <form method="GET" class="mb-8 rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[16rem]">
                    <label for="q" class="block text-sm font-medium text-slate-700">Search Documents</label>
                    <div class="relative mt-1.5">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 w-4 h-4 -translate-y-1/2 text-slate-400" />
                        <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Ordinance number, title, or keyword"
                               class="block w-full rounded-lg border-0 py-2.5 pl-9 pr-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                    </div>
                </div>
                <div>
                    <label for="category" class="block text-sm font-medium text-slate-700">Category</label>
                    <select id="category" name="category" data-auto-submit
                            class="mt-1.5 rounded-lg border-0 py-2.5 pl-3 pr-9 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                        <option value="">All Categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected($activeCategory === $category->slug)>{{ $category->name }} ({{ $category->documents_count }})</option>
                        @endforeach
                    </select>
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
                <button type="submit" class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Search</button>
                @if ($search || $activeCategory || $activeDepartment)
                    <a href="{{ route('site.documents') }}" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-white transition">Clear</a>
                @endif
            </div>
        </form>

        @if ($documents->count())
            <ul class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 divide-y divide-slate-100">
                @foreach ($documents as $document)
                    <li class="flex flex-wrap items-center gap-4 p-5">
                        <span class="inline-flex h-11 shrink-0 items-center rounded-lg bg-slate-100 px-3 text-xs font-bold text-slate-600 ring-1 ring-slate-200">{{ $document->extension() }}</span>
                        <div class="min-w-0 flex-1">
                            <h2 class="font-semibold text-slate-900">
                                <a href="{{ route('site.documents.show', $document->slug) }}" class="hover:text-brand-700 hover:underline">{{ $document->title }}</a>
                            </h2>
                            <p class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                                @if ($document->reference)<span class="font-medium text-slate-600">{{ $document->reference }}</span>@endif
                                @if ($document->category)<span>{{ $document->category->name }}</span>@endif
                                @if ($document->document_date)<span>{{ $document->document_date->format(config('municipal.date_format')) }}</span>@endif
                                <span>{{ $document->sizeDisplay() }}</span>
                            </p>
                        </div>
                        <a href="{{ route('site.documents.download', $document->slug) }}"
                           class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                            <x-icon name="download" class="w-4 h-4" /> Download
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-10">{{ $documents->links() }}</div>
        @else
            <x-site.empty title="No Documents Match Your Search" icon="archive"
                          message="Try a broader keyword, or browse by category. If you cannot find a record, contact the Clerk's office." />
        @endif
    </x-site.section>
</x-layouts.public>
