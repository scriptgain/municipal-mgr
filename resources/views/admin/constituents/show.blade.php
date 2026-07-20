<x-layouts.app :title="$record->name">
    <x-page-header :title="$record->name" icon="users"
                   subtitle="Resident Record. Staff Only.">
        <x-slot:actions>
            <x-button variant="secondary" icon="chevron-left" :href="route('constituents.index')">Back To Residents</x-button>
            <x-button variant="secondary" icon="edit" :href="route('constituents.edit', $record)">Edit Record</x-button>
            <span x-data @click="$dispatch('open-modal', 'log-contact')" class="inline-flex">
                <x-button icon="phone">Log A Contact</x-button>
            </span>
        </x-slot:actions>
    </x-page-header>

    {{-- Identity strip --}}
    <x-card>
        <div class="flex flex-wrap items-start gap-5">
            <x-avatar size="lg" :initials="$record->initials()" :name="$record->name" />

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $record->name }}</h2>
                    @if ($record->do_not_contact)
                        <x-badge color="danger" dot>Do Not Contact</x-badge>
                    @endif
                    @if ($record->user)
                        <x-badge color="info" dot>Has An Account</x-badge>
                    @endif
                    @foreach ($record->tagList() as $tag)
                        <x-badge color="neutral">{{ $tag }}</x-badge>
                    @endforeach
                </div>

                <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 text-sm">
                    <div class="flex items-start gap-2.5">
                        <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                            <x-icon name="envelope" class="w-4 h-4" />
                        </span>
                        <div class="min-w-0">
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Email</dt>
                            <dd class="mt-0.5 truncate text-slate-900">{{ $record->email ?: 'Not On File' }}</dd>
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                            <x-icon name="phone" class="w-4 h-4" />
                        </span>
                        <div class="min-w-0">
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Phone</dt>
                            <dd class="mt-0.5 truncate text-slate-900">{{ $record->phone ?: 'Not On File' }}</dd>
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                            <x-icon name="map-pin" class="w-4 h-4" />
                        </span>
                        <div class="min-w-0">
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Mailing Address</dt>
                            <dd class="mt-0.5 text-slate-900">
                                @forelse ($record->addressLines() as $line)
                                    <span class="block truncate">{{ $line }}</span>
                                @empty
                                    Not On File
                                @endforelse
                            </dd>
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                            <x-icon name="clock" class="w-4 h-4" />
                        </span>
                        <div class="min-w-0">
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Last Contact</dt>
                            <dd class="mt-0.5 truncate text-slate-900">
                                {{ $record->last_interaction_at?->format(config('municipal.date_format')) ?? 'Never' }}
                            </dd>
                        </div>
                    </div>
                </dl>
            </div>
        </div>
    </x-card>

    <div class="section-divider my-6"></div>

    <x-tabs :tabs="[
        'timeline' => ['label' => 'Timeline', 'icon' => 'clock', 'count' => $timeline->count()],
        'requests' => ['label' => 'Service Requests', 'icon' => 'bolt', 'count' => $counts['requests']],
        'submissions' => ['label' => 'Form Submissions', 'icon' => 'clipboard', 'count' => $counts['submissions']],
        'details' => ['label' => 'Details And Notes', 'icon' => 'file-text'],
        'merge' => ['label' => 'Merge Duplicates', 'icon' => 'copy', 'count' => $duplicates->count()],
    ]">

        {{-- ---------------------------------------------------------------
             Timeline: the whole point of the record. Service requests, form
             submissions and staff-logged contact in one chronological list.
        ---------------------------------------------------------------- --}}
        <x-tab-panel name="timeline">
            <x-card title="Everything On File" subtitle="Newest first. Filed online and logged by staff, together.">
                @if ($timeline->count())
                    <ol class="relative space-y-6 border-l border-slate-200 pl-6">
                        @foreach ($timeline as $entry)
                            <li class="relative">
                                <span class="absolute -left-[2.1rem] mt-0.5 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white text-brand-600 ring-1 ring-brand-100 shadow-sm">
                                    <x-icon :name="$entry['icon']" class="w-4 h-4" />
                                </span>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $entry['kind'] }}</span>
                                    @if ($entry['badge'])
                                        <x-badge :color="$entry['badge']['color']" dot>{{ $entry['badge']['label'] }}</x-badge>
                                    @endif
                                    <span class="text-xs text-slate-400">
                                        {{ $entry['at']->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}
                                    </span>
                                </div>
                                <p class="mt-1 font-medium text-slate-900">
                                    @if ($entry['href'])
                                        <a href="{{ $entry['href'] }}" class="hover:text-brand-700">{{ $entry['title'] }}</a>
                                    @else
                                        {{ $entry['title'] }}
                                    @endif
                                </p>
                                @if ($entry['summary'])
                                    <p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $entry['summary'] }}</p>
                                @endif
                                <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                    @foreach ($entry['meta'] as $meta)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $meta }}</span>
                                    @endforeach
                                    @if ($entry['actor'])
                                        <span>Logged By {{ $entry['actor'] }}</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <x-empty-state icon="clock" title="Nothing On File Yet"
                                   description="This resident has not filed anything, and no staff contact has been logged against the record." />
                @endif
            </x-card>
        </x-tab-panel>

        {{-- Service requests --}}
        <x-tab-panel name="requests">
            <x-card flush title="Service Requests" subtitle="Everything this resident reported from the public site.">
                @if ($requests->count())
                    <x-table flush>
                        <caption class="sr-only">Service Requests Filed By {{ $record->name }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Reference</th>
                                <th scope="col">Category</th>
                                <th scope="col">Location</th>
                                <th scope="col">Department</th>
                                <th scope="col">Status</th>
                                <th scope="col">Filed</th>
                                <th scope="col" class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $request)
                                <tr>
                                    <td class="font-medium text-slate-900 tabular">{{ $request->reference }}</td>
                                    <td>{{ $request->category }}</td>
                                    <td class="text-slate-600">{{ $request->location_text ?: '—' }}</td>
                                    <td>{{ $request->department?->name ?? 'Unassigned' }}</td>
                                    <td><x-badge :color="$request->statusColor()" dot>{{ $request->statusLabel() }}</x-badge></td>
                                    <td class="text-slate-500">{{ $request->created_at->format(config('municipal.date_format')) }}</td>
                                    <td class="text-right">
                                        <x-button size="sm" variant="secondary" :href="route('service-requests.show', $request)">Open</x-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @else
                    <x-admin.empty icon="bolt" title="No Service Requests"
                                   message="Nothing has been reported under this record." />
                @endif
            </x-card>
        </x-tab-panel>

        {{-- Form submissions --}}
        <x-tab-panel name="submissions">
            <x-card flush title="Form Submissions" subtitle="Forms this resident filled in on the public site.">
                @if ($submissions->count())
                    <x-table flush>
                        <caption class="sr-only">Form Submissions By {{ $record->name }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Form</th>
                                <th scope="col">Received</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($submissions as $submission)
                                <tr>
                                    <td class="font-medium text-slate-900">{{ $submission->form?->name ?? 'Deleted Form' }}</td>
                                    <td class="text-slate-500">{{ $submission->created_at->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}</td>
                                    <td>
                                        @if ($submission->isUnread())
                                            <x-badge color="warn" dot>Unread</x-badge>
                                        @else
                                            <x-badge color="success" dot>Read</x-badge>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <x-button size="sm" variant="secondary" :href="route('submissions.show', $submission)">Open</x-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @else
                    <x-admin.empty icon="clipboard" title="No Form Submissions"
                                   message="This resident has not submitted any of the town's online forms." />
                @endif
            </x-card>
        </x-tab-panel>

        {{-- Details and notes --}}
        <x-tab-panel name="details">
            <div class="grid gap-6 lg:grid-cols-3">
                <x-card class="lg:col-span-2" title="Staff Notes" subtitle="Internal. Never shown to the resident.">
                    @if ($record->notes)
                        <p class="whitespace-pre-line text-sm text-slate-700">{{ $record->notes }}</p>
                    @else
                        <p class="text-sm text-slate-500">No Notes Recorded Yet.</p>
                    @endif
                </x-card>

                <x-card title="Record Details">
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Record Created</dt>
                            <dd class="text-right text-slate-900">{{ $record->created_at->format(config('municipal.date_format')) }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Last Updated</dt>
                            <dd class="text-right text-slate-900">{{ $record->updated_at->format(config('municipal.date_format')) }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Created From</dt>
                            <dd class="text-right text-slate-900">{{ $record->source }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Linked Account</dt>
                            <dd class="text-right text-slate-900">{{ $record->user?->name ?? 'None' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Staff Logged Contacts</dt>
                            <dd class="text-right text-slate-900 tabular">{{ $counts['interactions'] }}</dd>
                        </div>
                    </dl>

                    <div class="section-divider mt-5 pt-5">
                        <x-button variant="secondary" icon="edit" :href="route('constituents.edit', $record)" class="w-full">Edit This Record</x-button>
                    </div>
                </x-card>
            </div>
        </x-tab-panel>

        {{-- Merge duplicates --}}
        <x-tab-panel name="merge">
            <x-card title="Merge A Duplicate Into This Record"
                    subtitle="Residents file under slightly different names and email addresses. Merging moves every request, submission and logged contact onto this record, then removes the duplicate.">
                @if ($duplicates->count())
                    <ul class="divide-y divide-slate-100">
                        @foreach ($duplicates as $duplicate)
                            <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <x-avatar size="sm" :initials="$duplicate->initials()" :name="$duplicate->name" />
                                    <div class="min-w-0">
                                        <a href="{{ route('constituents.show', $duplicate) }}"
                                           class="block truncate font-medium text-slate-900 hover:text-brand-700">{{ $duplicate->name }}</a>
                                        <p class="truncate text-xs text-slate-500">
                                            {{ $duplicate->email ?: 'No Email' }}
                                            @if ($duplicate->phone) &middot; {{ $duplicate->phone }} @endif
                                        </p>
                                    </div>
                                </div>
                                <span x-data @click="$dispatch('open-modal', 'merge-{{ $duplicate->id }}')" class="inline-flex">
                                    <x-button variant="secondary" size="sm" icon="copy">Merge Into This Record</x-button>
                                </span>

                                {{-- Modal confirm, never a native dialog. Carries the
                                     duplicate id, so the form is written out here
                                     rather than reusing the generic confirm helper. --}}
                                <x-modal :name="'merge-' . $duplicate->id" title="Merge These Two Records?"
                                         icon="warning" tone="warn" maxWidth="max-w-md">
                                    Everything filed by <span class="font-semibold">{{ $duplicate->name }}</span> moves onto
                                    <span class="font-semibold">{{ $record->name }}</span>. Blank fields on this record are filled
                                    in from the duplicate, and the duplicate is then deleted. This cannot be undone.
                                    <x-slot:footer>
                                        <x-button variant="secondary" size="sm"
                                                  x-on:click="$dispatch('close-modal', 'merge-{{ $duplicate->id }}')">Cancel</x-button>
                                        <form method="POST" action="{{ route('constituents.merge', $record) }}">
                                            @csrf
                                            <input type="hidden" name="duplicate_id" value="{{ $duplicate->id }}">
                                            <x-button size="sm" type="submit" icon="copy">Merge Records</x-button>
                                        </form>
                                    </x-slot:footer>
                                </x-modal>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <x-empty-state icon="copy" title="No Likely Duplicates Found"
                                   description="Nothing on file looks like the same person. Matching is on shared phone numbers and similar names." />
                @endif
            </x-card>
        </x-tab-panel>
    </x-tabs>

    {{-- Log a contact. A modal so the detail page never grows a long scroll. --}}
    <x-modal name="log-contact" title="Log A Contact" icon="phone"
             subtitle="Record a phone call, counter visit, email or letter against this resident."
             maxWidth="max-w-xl">
        <form method="POST" action="{{ route('constituents.interactions.store', $record) }}" class="space-y-4" id="log-contact-form">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Kind Of Contact" for="type" required :error="$errors->first('type')">
                    <x-select id="type" name="type" required>
                        @foreach ($interactionTypes as $key => $meta)
                            <option value="{{ $key }}">{{ $meta['label'] }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Direction" for="direction" required :error="$errors->first('direction')">
                    <x-select id="direction" name="direction" required>
                        @foreach ($directions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="When It Happened" for="occurred_at" required :error="$errors->first('occurred_at')">
                    <x-input type="datetime-local" id="occurred_at" name="occurred_at" required
                             :value="$defaultOccurredAt" />
                </x-field>

                <x-field label="Department" for="interaction_department" :error="$errors->first('department_id')">
                    <x-select id="interaction_department" name="department_id">
                        <option value="">Not Department Specific</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
            </div>

            <x-field label="Subject" for="subject" hint="Optional. A short line staff will scan later."
                     :error="$errors->first('subject')">
                <x-input id="subject" name="subject" maxlength="200" placeholder="Called about the water bill" />
            </x-field>

            <x-field label="What Was Said" for="note" required :error="$errors->first('note')">
                <textarea id="note" name="note" rows="4" required maxlength="5000"
                          class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500"></textarea>
            </x-field>
        </form>

        <x-slot:footer>
            <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'log-contact')">Cancel</x-button>
            <x-button size="sm" icon="check" type="submit" form="log-contact-form">Save Contact</x-button>
        </x-slot:footer>
    </x-modal>
</x-layouts.app>
