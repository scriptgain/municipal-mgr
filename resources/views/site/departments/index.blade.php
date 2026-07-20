<x-layouts.public title="Departments">
    <x-site.page-hero title="Departments"
                      subtitle="Who does what, and how to reach them directly."
                      :crumbs="[['label' => 'Departments']]" />

    <x-site.section :divider="false">
        @if ($departments->count())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($departments as $department)
                    <article class="flex h-full flex-col rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm transition hover:shadow-md">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-200">
                            <x-icon :name="$department->icon ?: 'building'" class="w-6 h-6" />
                        </span>
                        <h2 class="mt-4 text-lg font-semibold text-slate-900">
                            <a href="{{ route('site.departments.show', $department->slug) }}" class="hover:text-brand-700 hover:underline">{{ $department->name }}</a>
                        </h2>
                        @if ($department->summary)
                            <p class="mt-2 flex-1 text-sm leading-relaxed text-slate-600">{{ $department->summary }}</p>
                        @endif
                        <dl class="mt-4 space-y-1 text-sm">
                            @if ($department->phone)
                                <div class="flex items-center gap-2 text-slate-600">
                                    <x-icon name="phone" class="w-4 h-4 shrink-0 text-slate-400" />
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $department->phone) }}" class="hover:text-brand-700 hover:underline">{{ $department->phone }}</a>
                                </div>
                            @endif
                            @if ($department->email)
                                <div class="flex items-center gap-2 text-slate-600">
                                    <x-icon name="envelope" class="w-4 h-4 shrink-0 text-slate-400" />
                                    <a href="mailto:{{ $department->email }}" class="truncate hover:text-brand-700 hover:underline">{{ $department->email }}</a>
                                </div>
                            @endif
                        </dl>
                        <p class="mt-4 text-xs text-slate-400">{{ $department->staff_count }} Staff Listed</p>
                    </article>
                @endforeach
            </div>
        @else
            <x-site.empty title="No Departments Published" icon="building" />
        @endif
    </x-site.section>
</x-layouts.public>
