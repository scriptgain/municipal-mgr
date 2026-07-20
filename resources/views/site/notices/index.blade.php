<x-layouts.public title="Public Notices">
    <x-site.page-hero title="Public Notices"
                      subtitle="Legal notices, public hearings, ordinances, and election postings."
                      :crumbs="[['label' => 'Public Notices']]" />

    <x-site.section :divider="false">
        <x-tabs :tabs="[
            'current' => ['label' => 'Currently Posted', 'icon' => 'warning', 'count' => $current->total()],
            'expired' => ['label' => 'Recently Expired', 'icon' => 'archive', 'count' => $expired->count()],
        ]">
            <x-tab-panel name="current">
                @if ($current->count())
                    <ul class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 divide-y divide-slate-100">
                        @foreach ($current as $notice)
                            <li class="flex flex-wrap items-start gap-4 p-6">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                                    <x-icon name="warning" class="w-5 h-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h2 class="text-lg font-semibold text-slate-900">
                                        <a href="{{ route('site.notices.show', $notice->slug) }}" class="hover:text-brand-700 hover:underline">{{ $notice->title }}</a>
                                    </h2>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $notice->notice_type }}
                                        &middot; Posted {{ $notice->posted_at?->format(config('municipal.date_format')) }}
                                        @if ($notice->expires_at) &middot; Expires {{ $notice->expires_at->format(config('municipal.date_format')) }} @endif
                                    </p>
                                </div>
                                @if ($notice->document)
                                    <a href="{{ route('site.files.download', $notice->document->slug) }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-800 ring-1 ring-brand-200 transition hover:bg-brand-100">
                                        <x-icon name="download" class="w-4 h-4" /> Download
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-8">{{ $current->links() }}</div>
                @else
                    <x-site.empty title="No Notices Are Currently Posted" icon="warning"
                                  message="Legal notices appear here for the duration of their statutory posting period." />
                @endif
            </x-tab-panel>

            <x-tab-panel name="expired">
                @if ($expired->count())
                    <p class="mb-5 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200">
                        These notices are past their posting period. They remain available because a
                        posted notice is a public record.
                    </p>
                    <ul class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 divide-y divide-slate-100">
                        @foreach ($expired as $notice)
                            <li class="flex items-center justify-between gap-4 p-5">
                                <a href="{{ route('site.notices.show', $notice->slug) }}" class="font-medium text-slate-700 hover:text-brand-700 hover:underline">{{ $notice->title }}</a>
                                <span class="shrink-0 text-sm text-slate-400">Expired {{ $notice->expires_at?->format(config('municipal.date_format')) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <x-site.empty title="No Expired Notices" icon="archive" />
                @endif
            </x-tab-panel>
        </x-tabs>
    </x-site.section>
</x-layouts.public>
