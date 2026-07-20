<x-layouts.public :title="$record->fullName() . ' Booking Record'"
                  description="Booking record. An arrest is not a conviction.">
    <x-site.page-hero :title="$record->fullName()"
                      :eyebrow="$agencyName ?: 'Arrest Record'"
                      icon="shield"
                      :crumbs="[
                          ['label' => 'Arrest Records', 'href' => route('site.records.blotter')],
                          ['label' => $record->fullName()],
                      ]" />

    <x-site.section :divider="false">
        <x-site.records-disclaimer :disclaimer="$disclaimer"
                                   :takedownContact="$takedownContact"
                                   :retentionDays="$retentionDays" />
    </x-site.section>

    <x-site.section>
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <h2 class="font-display text-2xl font-semibold text-slate-900">Charges At Booking</h2>
                <span class="seal-rule mt-3 mb-5"></span>

                @if ($record->charges->count())
                    <div class="overflow-x-auto mm-scroll rounded-2xl ring-1 ring-slate-200 bg-white">
                        <table class="w-full text-left text-sm">
                            <caption class="sr-only">Charges recorded at the time of booking</caption>
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Charge</th>
                                    <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Statute</th>
                                    <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                                    <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Counts</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($record->charges as $charge)
                                    <tr>
                                        <td class="px-5 py-4 font-medium text-slate-900">{{ $charge->description }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ $charge->statute ?: ': ' }}</td>
                                        <td class="px-5 py-4"><x-badge :color="$charge->severityColor()">{{ $charge->severityLabel() }}</x-badge></td>
                                        <td class="px-5 py-4 tabular text-slate-600">{{ $charge->counts }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-slate-600">No charges are recorded for this booking.</p>
                @endif

                <div class="section-divider mt-10 pt-10">
                    <h2 class="font-display text-2xl font-semibold text-slate-900">Case Disposition</h2>
                    <span class="seal-rule mt-3 mb-5"></span>

                    <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                        <div class="flex flex-wrap items-center gap-3">
                            <x-badge :color="$record->dispositionColor()" dot>{{ $record->dispositionLabel() }}</x-badge>
                            @if ($record->disposition_updated_at)
                                <span class="text-sm text-slate-500">Updated {{ $record->disposition_updated_at->format(config('municipal.date_format')) }}</span>
                            @endif
                        </div>
                        <p class="mt-3 text-slate-700">{{ $record->dispositionExplanation() }}</p>
                        @if ($record->disposition_note)
                            <p class="mt-2 text-sm text-slate-600">{{ $record->disposition_note }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <aside class="space-y-6">
                @if ($record->showsMugshot())
                    <figure class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                        <img src="{{ municipal_upload_url($record->mugshot_path) }}"
                             alt="Booking photograph of {{ $record->fullName() }}"
                             class="w-full rounded-xl object-cover ring-1 ring-slate-200">
                        <figcaption class="mt-3 text-xs leading-relaxed text-slate-500">
                            Booking photograph taken at intake. It is not evidence of guilt.
                        </figcaption>
                    </figure>
                @endif

                <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">Booking Details</h2>
                    <span class="seal-rule mt-3 mb-4"></span>
                    <dl class="space-y-3 text-sm">
                        @if ($record->age)
                            <div>
                                <dt class="font-medium text-slate-500">Age At Booking</dt>
                                <dd class="text-slate-900">{{ $record->age }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="font-medium text-slate-500">Booked</dt>
                            <dd class="text-slate-900">{{ $record->booked_at->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}</dd>
                        </div>
                        @if ($record->released_at)
                            <div>
                                <dt class="font-medium text-slate-500">Released</dt>
                                <dd class="text-slate-900">{{ $record->released_at->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="font-medium text-slate-500">Arresting Agency</dt>
                            <dd class="text-slate-900">{{ $record->arresting_agency }}</dd>
                        </div>
                        @if ($showCaseNumber && $record->case_number)
                            <div>
                                <dt class="font-medium text-slate-500">Case Number</dt>
                                <dd class="text-slate-900">{{ $record->case_number }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="font-medium text-slate-500">Custody Status</dt>
                            <dd class="mt-1"><x-badge :color="$record->custodyColor()">{{ $record->custodyLabel() }}</x-badge></dd>
                        </div>
                        @if ($showBond && $record->bond_amount !== null)
                            <div>
                                <dt class="font-medium text-slate-500">Bond</dt>
                                <dd class="tabular text-slate-900">${{ number_format((float) $record->bond_amount, 2) }}</dd>
                                @if ($record->bond_note)<dd class="text-xs text-slate-500">{{ $record->bond_note }}</dd>@endif
                            </div>
                        @endif
                    </dl>
                </div>

                @if ($takedownContact)
                    <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                        <h2 class="font-semibold text-slate-900">Request A Correction Or Removal</h2>
                        <span class="seal-rule mt-3 mb-4"></span>
                        <p class="text-sm leading-relaxed text-slate-700">{{ $takedownContact }}</p>
                        <p class="mt-3 text-sm leading-relaxed text-slate-500">
                            If a court has sealed or expunged this case, contact us and the record will be removed.
                        </p>
                    </div>
                @endif

                <a href="{{ route('site.records.blotter') }}"
                   class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">
                    <x-icon name="chevron-left" class="w-4 h-4" aria-hidden="true" /> Back To Arrest Records
                </a>
            </aside>
        </div>
    </x-site.section>
</x-layouts.public>
