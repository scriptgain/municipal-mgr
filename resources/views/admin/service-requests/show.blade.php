<x-layouts.app :title="'Request ' . $record->reference">
    <x-page-header :title="$record->reference" icon="bolt" :subtitle="$record->category">
        <x-slot:actions>
            <x-button variant="secondary" icon="chevron-left" :href="route('service-requests.index')">Back To Queue</x-button>
            <x-button variant="secondary" icon="eye" href="{{ route('site.report.status', $record->tracking_token) }}" target="_blank" rel="noopener">
                Resident's View
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <x-card title="What Was Reported">
                <p class="whitespace-pre-line text-slate-700">{{ $record->description }}</p>

                @if ($record->photo_path)
                    <img src="{{ municipal_upload_url($record->photo_path) }}" alt="Photograph submitted with request {{ $record->reference }}"
                         class="mt-5 max-h-96 rounded-xl object-cover ring-1 ring-slate-200">
                @endif

                <dl class="section-divider mt-6 grid gap-4 pt-5 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="font-medium text-slate-500">Location</dt>
                        <dd class="mt-0.5 text-slate-900">{{ $record->location_text ?: 'Not Provided' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Received</dt>
                        <dd class="mt-0.5 text-slate-900">{{ $record->created_at->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Reported By</dt>
                        <dd class="mt-0.5 text-slate-900">
                            @if ($record->is_anonymous)
                                Anonymous
                            @elseif ($record->constituent)
                                <a href="{{ route('constituents.show', $record->constituent) }}"
                                   class="inline-flex items-center gap-1.5 font-medium text-brand-700 hover:text-brand-800">
                                    <x-icon name="users" class="w-4 h-4" />
                                    {{ $record->constituent->name }}
                                </a>
                                <span class="mt-0.5 block text-xs text-slate-500">Opens The Full Resident Record</span>
                            @else
                                {{ $record->reporter_name ?: 'Not Provided' }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Contact</dt>
                        <dd class="mt-0.5 text-slate-900">
                            {{ $record->reporter_email ?: '—' }}
                            @if ($record->reporter_phone) &middot; {{ $record->reporter_phone }} @endif
                        </dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Activity" subtitle="Public updates are visible to the resident on their tracking page.">
                <form method="POST" action="{{ route('service-requests.updates.store', $record) }}" class="space-y-4">
                    @csrf
                    <x-field label="Add A Note" for="note" required :error="$errors->first('note')">
                        <textarea id="note" name="note" rows="3" required
                                  class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600"></textarea>
                    </x-field>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <x-toggle name="is_public" :checked="true" label="Show This Note To The Resident"
                                  description="Turn off for internal crew notes." />
                        <x-button type="submit" icon="plus" size="sm">Add Note</x-button>
                    </div>
                </form>

                <ul class="section-divider mt-6 space-y-4 pt-5">
                    @forelse ($record->updatesLog as $update)
                        <li class="flex gap-3">
                            <x-avatar size="sm" :initials="$update->user?->initials() ?? 'SYS'" :name="$update->user?->name" />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-medium text-slate-900">{{ $update->user?->name ?? 'System' }}</span>
                                    <span class="text-xs text-slate-400">{{ $update->created_at->diffForHumans() }}</span>
                                    @if (! $update->is_public)
                                        <x-badge color="neutral">Internal Only</x-badge>
                                    @endif
                                </div>
                                <p class="mt-0.5 whitespace-pre-line text-sm text-slate-600">{{ $update->note }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No Activity Recorded Yet.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>

        <div>
            <x-card title="Triage">
                <form method="POST" action="{{ route('service-requests.update', $record) }}" class="space-y-5">
                    @csrf @method('PUT')

                    <x-field label="Status" for="status" :error="$errors->first('status')">
                        <x-select id="status" name="status">
                            @foreach ($statuses as $key => $meta)
                                <option value="{{ $key }}" @selected($record->status === $key)>{{ $meta['label'] }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Priority" for="priority" :error="$errors->first('priority')">
                        <x-select id="priority" name="priority">
                            @foreach (['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'] as $value => $priorityLabel)
                                <option value="{{ $value }}" @selected($record->priority === $value)>{{ $priorityLabel }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Department" for="department_id" :error="$errors->first('department_id')">
                        <x-select id="department_id" name="department_id">
                            <option value="">Unassigned</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected($record->department_id == $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Assigned To" for="assigned_to" :error="$errors->first('assigned_to')">
                        <x-select id="assigned_to" name="assigned_to">
                            <option value="">Nobody Yet</option>
                            @foreach ($staff as $member)
                                <option value="{{ $member->id }}" @selected($record->assigned_to == $member->id)>{{ $member->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-button type="submit" icon="check" class="w-full">Save Triage</x-button>
                </form>

                <dl class="section-divider mt-6 space-y-3 pt-5 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Acknowledged</dt>
                        <dd class="text-slate-900">{{ $record->acknowledged_at?->format(config('municipal.date_format')) ?? 'Not Yet' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Resolved</dt>
                        <dd class="text-slate-900">{{ $record->resolved_at?->format(config('municipal.date_format')) ?? 'Not Yet' }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
</x-layouts.app>
