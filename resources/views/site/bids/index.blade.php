<x-layouts.public title="Bids And RFPs">
    <x-site.page-hero title="Bids And Requests For Proposals"
                      subtitle="Current procurement opportunities. Submissions received after the closing time cannot be accepted."
                      :crumbs="[['label' => 'Bids And RFPs']]" />

    <x-site.section :divider="false">
        <x-tabs :tabs="[
            'open' => ['label' => 'Open Opportunities', 'icon' => 'database', 'count' => $open->count()],
            'closed' => ['label' => 'Closed And Awarded', 'icon' => 'archive', 'count' => $closed->total()],
        ]">
            <x-tab-panel name="open">
                @if ($open->count())
                    <ul class="space-y-4">
                        @foreach ($open as $bid)
                            <li class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-200">{{ $bid->bid_type }}</span>
                                            @if ($bid->reference)<span class="text-xs font-medium text-slate-500">{{ $bid->reference }}</span>@endif
                                        </div>
                                        <h2 class="mt-2 text-xl font-semibold text-slate-900">
                                            <a href="{{ route('site.bids.show', $bid->slug) }}" class="hover:text-brand-700 hover:underline">{{ $bid->title }}</a>
                                        </h2>
                                        @if ($bid->department)<p class="mt-1 text-sm text-slate-500">{{ $bid->department->name }}</p>@endif
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Closes</p>
                                        <p class="text-sm font-semibold text-slate-900">
                                            {{ $bid->closes_at?->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) ?? 'Open' }}
                                        </p>
                                        @if ($bid->document)
                                            <a href="{{ route('site.documents.download', $bid->document->slug) }}"
                                               class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline">
                                                <x-icon name="download" class="w-4 h-4" /> Bid Package
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <x-site.empty title="No Open Opportunities" icon="database"
                                  message="New bids and requests for proposals are posted here as they are issued." />
                @endif
            </x-tab-panel>

            <x-tab-panel name="closed">
                @if ($closed->count())
                    <div class="overflow-x-auto mm-scroll rounded-2xl ring-1 ring-slate-200 bg-white">
                        <table class="w-full text-left text-sm">
                            <caption class="sr-only">Closed and awarded procurement opportunities</caption>
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Opportunity</th>
                                    <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Closed</th>
                                    <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Awarded To</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($closed as $bid)
                                    <tr class="hover:bg-brand-50/40">
                                        <td class="px-5 py-4">
                                            <a href="{{ route('site.bids.show', $bid->slug) }}" class="font-medium text-slate-900 hover:text-brand-700 hover:underline">{{ $bid->title }}</a>
                                        </td>
                                        <td class="px-5 py-4 text-slate-600">{{ $bid->closes_at?->format(config('municipal.date_format')) ?? '—' }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ $bid->awarded_to ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-8">{{ $closed->links() }}</div>
                @else
                    <x-site.empty title="No Closed Opportunities On Record" icon="archive" />
                @endif
            </x-tab-panel>
        </x-tabs>
    </x-site.section>
</x-layouts.public>
