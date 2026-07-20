<x-layouts.public title="Community Calendar">
    <x-site.page-hero title="Community Calendar"
                      :subtitle="'Events and meetings for ' . $month->format('F Y') . '.'"
                      :crumbs="[['label' => 'Events', 'href' => route('site.events')], ['label' => 'Calendar']]" />

    <x-site.section :divider="false">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('site.calendar', ['year' => $prev->year, 'month' => $prev->month]) }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50">
                <x-icon name="chevron-left" class="w-4 h-4" /> {{ $prev->format('F Y') }}
            </a>
            <h2 class="font-display text-2xl font-semibold text-slate-900">{{ $month->format('F Y') }}</h2>
            <a href="{{ route('site.calendar', ['year' => $next->year, 'month' => $next->month]) }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50">
                {{ $next->format('F Y') }} <x-icon name="chevron-right" class="w-4 h-4" />
            </a>
        </div>

        <div class="overflow-x-auto mm-scroll">
            <table class="w-full min-w-[46rem] border-collapse overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200">
                <caption class="sr-only">Community events for {{ $month->format('F Y') }}</caption>
                <thead>
                    <tr class="bg-slate-50">
                        @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $weekday)
                            <th scope="col" class="border-b border-slate-200 px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <span class="hidden sm:inline">{{ $weekday }}</span>
                                <span class="sm:hidden">{{ substr($weekday, 0, 3) }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($weeks as $week)
                        <tr>
                            @foreach ($week as $day)
                                <td @class([
                                    'h-32 border border-slate-100 p-2 align-top',
                                    'bg-slate-50/60 text-slate-400' => ! $day['in_month'],
                                    'bg-brand-50/60' => $day['is_today'],
                                ])>
                                    <span @class([
                                        'inline-flex h-7 w-7 items-center justify-center rounded-full text-sm tabular',
                                        'bg-brand-700 font-semibold text-white' => $day['is_today'],
                                        'text-slate-700' => ! $day['is_today'] && $day['in_month'],
                                    ])>{{ $day['day'] }}</span>

                                    <ul class="mt-1.5 space-y-1">
                                        @foreach ($day['events'] as $event)
                                            <li>
                                                <a href="{{ route('site.events.show', $event->slug) }}"
                                                   class="block truncate rounded bg-brand-100 px-1.5 py-1 text-[11px] font-medium text-brand-900 hover:bg-brand-200 transition"
                                                   data-tip="{{ $event->title }} – {{ $event->whenDisplay() }}">
                                                    {{ $event->title }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-site.section>
</x-layouts.public>
