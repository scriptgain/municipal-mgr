<x-layouts.public :title="$job->title">
    <x-site.page-hero :title="$job->title" :eyebrow="$job->employment_type"
                      :subtitle="$job->department?->name"
                      :crumbs="[['label' => 'Jobs', 'href' => route('site.jobs')], ['label' => $job->title]]" />

    <x-site.section :divider="false">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="min-w-0 lg:col-span-2 space-y-8">
                <div>
                    <h2 class="font-display text-2xl font-semibold text-slate-900">Position Summary</h2>
                    <span class="seal-rule mt-3 mb-5"></span>
                    <div class="prose-civic">{!! $job->description !!}</div>
                </div>

                @if ($job->requirements)
                    <div class="section-divider pt-8">
                        <h2 class="font-display text-2xl font-semibold text-slate-900">Requirements And Qualifications</h2>
                        <span class="seal-rule mt-3 mb-5"></span>
                        <div class="prose-civic">{!! $job->requirements !!}</div>
                    </div>
                @endif
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">At A Glance</h2>
                    <span class="seal-rule mt-3 mb-4"></span>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="font-medium text-slate-500">Employment Type</dt>
                            <dd class="text-slate-900">{{ $job->employment_type }}</dd>
                        </div>
                        @if ($job->salary_range)
                            <div>
                                <dt class="font-medium text-slate-500">Compensation</dt>
                                <dd class="text-slate-900">{{ $job->salary_range }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="font-medium text-slate-500">Closes</dt>
                            <dd class="text-slate-900">{{ $job->closesDisplay() }}</dd>
                        </div>
                        @if ($job->posted_on)
                            <div>
                                <dt class="font-medium text-slate-500">Posted</dt>
                                <dd class="text-slate-900">{{ $job->posted_on->format(config('municipal.date_format')) }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">How To Apply</h2>
                    <span class="seal-rule mt-3 mb-4"></span>
                    <div class="space-y-3">
                        @if ($job->apply_url)
                            <a href="{{ $job->apply_url }}" target="_blank" rel="noopener"
                               class="flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-800">
                                Apply Online <x-icon name="external" class="w-4 h-4" />
                            </a>
                        @endif
                        @if ($job->applicationForm)
                            <a href="{{ route('site.files.download', $job->applicationForm->slug) }}"
                               class="flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-3 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50">
                                <x-icon name="download" class="w-4 h-4" /> Download The Application
                            </a>
                        @endif
                        @if ($job->apply_email)
                            <p class="text-sm text-slate-600">
                                Send completed applications to
                                <a href="mailto:{{ $job->apply_email }}" class="font-medium text-brand-700 hover:underline">{{ $job->apply_email }}</a>.
                            </p>
                        @endif
                        @if (! $job->apply_url && ! $job->applicationForm && ! $job->apply_email)
                            <p class="text-sm text-slate-600">Contact Village Hall for application instructions.</p>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </x-site.section>
</x-layouts.public>
