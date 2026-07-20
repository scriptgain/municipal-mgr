<x-layouts.public :title="$notice->title">
    <x-site.page-hero :title="$notice->title" :eyebrow="$notice->notice_type"
                      :crumbs="[['label' => 'Public Notices', 'href' => route('site.notices')], ['label' => $notice->title]]" />

    <x-site.section :divider="false">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                @if ($notice->isExpired())
                    <div class="mb-6 rounded-xl bg-slate-100 px-5 py-4 text-sm text-slate-700 ring-1 ring-slate-200">
                        This notice expired on {{ $notice->expires_at->format(config('municipal.date_format')) }}.
                        It is retained here as a public record.
                    </div>
                @endif

                <div class="prose-civic">{!! $notice->body !!}</div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">Notice Details</h2>
                    <span class="seal-rule mt-3 mb-4"></span>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="font-medium text-slate-500">Type</dt>
                            <dd class="text-slate-900">{{ $notice->notice_type }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Posted</dt>
                            <dd class="text-slate-900">{{ $notice->posted_at?->format(config('municipal.date_format')) ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Expires</dt>
                            <dd class="text-slate-900">{{ $notice->expires_at?->format(config('municipal.date_format')) ?? 'No Expiry' }}</dd>
                        </div>
                        @if ($notice->department)
                            <div>
                                <dt class="font-medium text-slate-500">Department</dt>
                                <dd><a href="{{ route('site.departments.show', $notice->department->slug) }}" class="text-brand-700 hover:underline">{{ $notice->department->name }}</a></dd>
                            </div>
                        @endif
                    </dl>
                </div>

                @if ($notice->document)
                    <a href="{{ route('site.documents.download', $notice->document->slug) }}"
                       class="flex items-center gap-3 rounded-2xl bg-brand-700 p-5 text-white transition hover:bg-brand-800">
                        <x-icon name="download" class="w-6 h-6 shrink-0" />
                        <span>
                            <span class="block font-semibold">Download The Official Notice</span>
                            <span class="block text-sm text-brand-100">{{ $notice->document->file_name }}</span>
                        </span>
                    </a>
                @endif
            </aside>
        </div>
    </x-site.section>
</x-layouts.public>
