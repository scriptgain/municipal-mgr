<x-layouts.public title="Employment Opportunities">
    <x-site.page-hero title="Employment Opportunities"
                      subtitle="Work for your community. Current openings with the municipality."
                      :crumbs="[['label' => 'Jobs']]" />

    <x-site.section :divider="false">
        @if ($jobs->count())
            <ul class="space-y-4">
                @foreach ($jobs as $job)
                    <li class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-200">{{ $job->employment_type }}</span>
                                    @if ($job->department)
                                        <span class="text-xs text-slate-500">{{ $job->department->name }}</span>
                                    @endif
                                </div>
                                <h2 class="mt-2 text-xl font-semibold text-slate-900">
                                    <a href="{{ route('site.jobs.show', $job->slug) }}" class="hover:text-brand-700 hover:underline">{{ $job->title }}</a>
                                </h2>
                                @if ($job->salary_range)
                                    <p class="mt-1 text-sm text-slate-600">{{ $job->salary_range }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Closes</p>
                                <p class="text-sm font-semibold text-slate-900">{{ $job->closesDisplay() }}</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <x-site.empty title="No Open Positions Right Now" icon="users"
                          message="Check back soon, or contact Village Hall about upcoming openings." />
        @endif

        @if ($closed->count())
            <div class="section-divider mt-12 pt-8">
                <h2 class="font-display text-xl font-semibold text-slate-900">Recently Closed Postings</h2>
                <ul class="mt-4 flex flex-wrap gap-3">
                    @foreach ($closed as $job)
                        <li class="rounded-full bg-slate-100 px-4 py-2 text-sm text-slate-600">
                            {{ $job->title }} <span class="text-slate-400">(closed {{ $job->closes_at?->format(config('municipal.date_format')) }})</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-site.section>
</x-layouts.public>
