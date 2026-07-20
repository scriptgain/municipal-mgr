<x-layouts.public title="News And Announcements">
    <x-site.page-hero title="News And Announcements"
                      subtitle="Press releases, service alerts, and updates from your municipal government."
                      :crumbs="[['label' => 'News']]" />

    <x-site.section :divider="false">
        <form method="GET" class="mb-8 flex flex-wrap items-end gap-3 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
            <div>
                <label for="category" class="block text-sm font-medium text-slate-700">Category</label>
                <select id="category" name="category" data-auto-submit
                        class="mt-1.5 rounded-lg border-0 py-2.5 pl-3 pr-9 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected($activeCategory === $category)>{{ $category }}</option>
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
            <button type="submit" class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Apply Filters</button>
            @if ($activeCategory || $activeDepartment)
                <a href="{{ route('site.news') }}" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-white transition">Clear</a>
            @endif
        </form>

        @if ($posts->count())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <article class="flex flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm transition hover:shadow-md">
                        @if ($post->image_path)
                            <img src="{{ municipal_upload_url($post->image_path) }}" alt="" class="aspect-[16/9] w-full object-cover">
                        @endif
                        <div class="flex flex-1 flex-col p-6">
                            <p class="text-xs font-semibold uppercase tracking-wide text-seal-700">
                                {{ $post->category }} &middot; {{ $post->published_at?->format(config('municipal.date_format')) }}
                            </p>
                            <h2 class="mt-2 text-lg font-semibold text-slate-900">
                                <a href="{{ route('site.news.show', $post->slug) }}" class="hover:text-brand-700 hover:underline">{{ $post->title }}</a>
                            </h2>
                            <p class="mt-2 flex-1 text-sm leading-relaxed text-slate-600">{{ $post->teaser() }}</p>
                            @if ($post->department)
                                <p class="mt-4 text-xs text-slate-400">{{ $post->department->name }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-10">{{ $posts->links() }}</div>
        @else
            <x-site.empty title="No News Matches Those Filters" icon="bell"
                          message="Try clearing the filters to see everything that has been posted." />
        @endif
    </x-site.section>
</x-layouts.public>
