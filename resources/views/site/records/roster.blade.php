<x-layouts.public title="Inmate Roster"
                  description="People currently held in custody. An arrest is not a conviction.">
    <x-site.page-hero title="Inmate Roster"
                      :eyebrow="$agencyName"
                      subtitle="People currently held in custody, drawn from the booking records published by the department."
                      icon="lock"
                      :crumbs="[
                          ['label' => 'Arrest Records', 'href' => route('site.records.blotter')],
                          ['label' => 'Inmate Roster'],
                      ]" />

    <x-site.section :divider="false">
        <x-site.records-disclaimer :disclaimer="$disclaimer" :takedownContact="$takedownContact" compact />
    </x-site.section>

    <x-site.section title="Currently In Custody"
                    :subtitle="$records->total() . ' person(s) in custody at the time this page was generated.'"
                    :href="route('site.records.blotter')" linkLabel="View All Arrest Records">
        @if ($records->count())
            <div class="overflow-x-auto mm-scroll rounded-2xl ring-1 ring-slate-200 bg-white">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">People currently held in custody</caption>
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                            <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Age</th>
                            <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Booked</th>
                            <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Charges</th>
                            @if ($showBond)
                                <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Bond</th>
                            @endif
                            <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Disposition</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($records as $record)
                            <tr class="hover:bg-brand-50/40">
                                <td class="px-5 py-4">
                                    <a href="{{ route('site.records.show', $record->public_ref) }}"
                                       class="font-medium text-slate-900 hover:text-brand-700 hover:underline">{{ $record->fullName() }}</a>
                                    @if ($showCaseNumber && $record->case_number)
                                        <span class="block text-xs text-slate-400">Case {{ $record->case_number }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 tabular text-slate-600">{{ $record->age ?: ': ' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $record->booked_at->format(config('municipal.date_format')) }}</td>
                                <td class="px-5 py-4 text-slate-600">
                                    @if ($record->charges->count())
                                        <ul class="flex flex-wrap gap-1.5">
                                            @foreach ($record->charges as $charge)
                                                <li><x-badge :color="$charge->severityColor()">{{ $charge->description }}</x-badge></li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-slate-400">None Recorded</span>
                                    @endif
                                </td>
                                @if ($showBond)
                                    <td class="px-5 py-4 tabular text-slate-600">
                                        {{ $record->bond_amount !== null ? '$' . number_format((float) $record->bond_amount, 2) : ': ' }}
                                    </td>
                                @endif
                                <td class="px-5 py-4">
                                    <x-badge :color="$record->dispositionColor()" dot>{{ $record->dispositionLabel() }}</x-badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-8">{{ $records->links() }}</div>
        @else
            <x-site.empty title="Nobody Is Currently In Custody" icon="lock"
                          message="When someone is booked and held, they appear on this roster until they are released." />
        @endif
    </x-site.section>
</x-layouts.public>
