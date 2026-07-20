<div x-data="publishPanel({{ old('is_published', $record->is_published ?? false) ? 'true' : 'false' }}, '{{ old('age', $record->age) }}', {{ $minimumAge }})">

    <x-card flush>
        <div class="px-5 sm:px-6 pt-2">
            <x-tabs :tabs="[
                'subject' => ['label' => 'Subject And Booking', 'icon' => 'users'],
                'charges' => ['label' => 'Charges', 'icon' => 'scale'],
                'case' => ['label' => 'Custody And Disposition', 'icon' => 'clipboard'],
                'photo' => ['label' => 'Booking Photograph', 'icon' => 'eye'],
                'publication' => ['label' => 'Publication', 'icon' => 'globe'],
            ]">

                {{-- ---------------------------------------------- Subject --}}
                <x-tab-panel name="subject">
                    <div class="grid gap-5 sm:grid-cols-2 pb-6">
                        <x-field label="First Name" for="first_name" required :error="$errors->first('first_name')">
                            <x-input id="first_name" name="first_name" :value="old('first_name', $record->first_name)" required />
                        </x-field>

                        <x-field label="Last Name" for="last_name" required :error="$errors->first('last_name')">
                            <x-input id="last_name" name="last_name" :value="old('last_name', $record->last_name)" required />
                        </x-field>

                        <x-field label="Middle Name" for="middle_name" :error="$errors->first('middle_name')">
                            <x-input id="middle_name" name="middle_name" :value="old('middle_name', $record->middle_name)" />
                        </x-field>

                        <x-field label="Suffix" for="suffix" hint="Jr, Sr, III." :error="$errors->first('suffix')">
                            <x-input id="suffix" name="suffix" :value="old('suffix', $record->suffix)" />
                        </x-field>

                        <x-field label="Age At Booking" for="age"
                                 hint="Age only. Dates of birth are not stored: an age serves the public interest, a date of birth serves identity thieves."
                                 :error="$errors->first('age')">
                            <x-input id="age" name="age" type="number" min="0" max="120"
                                     :value="old('age', $record->age)"
                                     x-on:input="syncAge($event.target.value)" />
                        </x-field>

                        <x-field label="Arresting Agency" for="arresting_agency" required :error="$errors->first('arresting_agency')">
                            <x-input id="arresting_agency" name="arresting_agency" :value="old('arresting_agency', $record->arresting_agency)" required />
                        </x-field>

                        <x-field label="Booking Date And Time" for="booked_at" required :error="$errors->first('booked_at')">
                            <x-input id="booked_at" name="booked_at" type="datetime-local" required
                                     :value="old('booked_at', $record->booked_at?->format('Y-m-d\TH:i'))" />
                        </x-field>

                        <x-field label="Release Date And Time" for="released_at" hint="Leave empty while the subject is still in custody." :error="$errors->first('released_at')">
                            <x-input id="released_at" name="released_at" type="datetime-local"
                                     :value="old('released_at', $record->released_at?->format('Y-m-d\TH:i'))" />
                        </x-field>

                        <x-field label="Case Number" for="case_number" :error="$errors->first('case_number')">
                            <x-input id="case_number" name="case_number" :value="old('case_number', $record->case_number)" />
                        </x-field>

                        <x-field label="Booking Number" for="booking_number" :error="$errors->first('booking_number')">
                            <x-input id="booking_number" name="booking_number" :value="old('booking_number', $record->booking_number)" />
                        </x-field>

                        <div class="min-w-0 sm:col-span-2" x-show="blocked" x-cloak>
                            <x-alert type="warn" title="This Subject Is A Juvenile">
                                A subject under {{ $minimumAge }} cannot be published. The record can still be created and kept for
                                staff use, but it will never appear on the public blotter or the inmate roster.
                            </x-alert>
                        </div>
                    </div>
                </x-tab-panel>

                {{-- ---------------------------------------------- Charges --}}
                <x-tab-panel name="charges">
                    <div class="pb-6" x-data="chargeRows({{ $chargeRows }}, {{ $severitiesJson }}, 'misdemeanor')">
                        <p class="mb-4 text-sm text-slate-500">
                            One row per charge. Charges are shown on the public record next to the disposition,
                            so a booking with four counts should be four rows, not one line of text.
                        </p>

                        <div class="space-y-3">
                            <template x-for="(row, index) in rows" :key="index">
                                <div class="grid gap-3 rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-200 sm:grid-cols-12">
                                    <div class="min-w-0 sm:col-span-5 space-y-1.5">
                                        <label class="block text-sm font-medium text-slate-700" :for="'charge-desc-' + index">Charge</label>
                                        <input type="text" :id="'charge-desc-' + index" :name="'charges[' + index + '][description]'"
                                               x-model="row.description" placeholder="Driving On A Suspended License"
                                               class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                    </div>

                                    <div class="min-w-0 sm:col-span-3 space-y-1.5">
                                        <label class="block text-sm font-medium text-slate-700" :for="'charge-statute-' + index">Statute</label>
                                        <input type="text" :id="'charge-statute-' + index" :name="'charges[' + index + '][statute]'"
                                               x-model="row.statute" placeholder="ARS 28-3473"
                                               class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                    </div>

                                    <div class="min-w-0 sm:col-span-2 space-y-1.5">
                                        <label class="block text-sm font-medium text-slate-700" :for="'charge-severity-' + index">Class</label>
                                        <select :id="'charge-severity-' + index" :name="'charges[' + index + '][severity]'" x-model="row.severity"
                                                class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                            <template x-for="(label, key) in severities" :key="key">
                                                <option :value="key" x-text="label"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div class="min-w-0 sm:col-span-1 space-y-1.5">
                                        <label class="block text-sm font-medium text-slate-700" :for="'charge-counts-' + index">Counts</label>
                                        <input type="number" min="1" max="999" :id="'charge-counts-' + index" :name="'charges[' + index + '][counts]'"
                                               x-model="row.counts"
                                               class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                    </div>

                                    <div class="min-w-0 sm:col-span-1 flex items-end gap-1">
                                        <button type="button" @click="moveUp(index)" aria-label="Move Charge Up" data-tip="Move Up"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-slate-500 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-100">
                                            <x-icon name="chevron-up" class="w-4 h-4" aria-hidden="true" />
                                        </button>
                                        <button type="button" @click="remove(index)" aria-label="Remove Charge" data-tip="Remove Charge"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-rose-600 ring-1 ring-inset ring-rose-200 transition hover:bg-rose-50">
                                            <x-icon name="trash" class="w-4 h-4" aria-hidden="true" />
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-4">
                            <x-button type="button" variant="secondary" size="sm" icon="plus" x-on:click="add()">Add Another Charge</x-button>
                        </div>
                    </div>
                </x-tab-panel>

                {{-- --------------------------- Custody and disposition --}}
                <x-tab-panel name="case">
                    <div class="grid gap-5 sm:grid-cols-2 pb-6">
                        <x-field label="Custody Status" for="custody_status" required
                                 hint="Anyone marked In Custody appears on the inmate roster."
                                 :error="$errors->first('custody_status')">
                            <x-select id="custody_status" name="custody_status">
                                @foreach ($custodyStatuses as $key => $status)
                                    <option value="{{ $key }}" @selected(old('custody_status', $record->custody_status ?: 'in_custody') === $key)>{{ $status['label'] }}</option>
                                @endforeach
                            </x-select>
                        </x-field>

                        <x-field label="Disposition" for="disposition" required
                                 hint="An arrest is not a conviction. Keep this current: a booking published without its outcome follows someone for years."
                                 :error="$errors->first('disposition')">
                            <x-select id="disposition" name="disposition">
                                @foreach ($dispositions as $key => $option)
                                    <option value="{{ $key }}" @selected(old('disposition', $record->disposition ?: 'pending') === $key)>{{ $option['label'] }}</option>
                                @endforeach
                            </x-select>
                        </x-field>

                        <x-field label="Disposition Note" for="disposition_note" class="sm:col-span-2"
                                 hint="Shown publicly beneath the disposition. Keep it factual."
                                 :error="$errors->first('disposition_note')">
                            <x-input id="disposition_note" name="disposition_note" :value="old('disposition_note', $record->disposition_note)" />
                        </x-field>

                        <x-field label="Bond Amount" for="bond_amount" :error="$errors->first('bond_amount')">
                            <x-input id="bond_amount" name="bond_amount" type="number" step="0.01" min="0"
                                     :value="old('bond_amount', $record->bond_amount)" />
                        </x-field>

                        <x-field label="Bond Note" for="bond_note" hint="For example: Released on own recognizance." :error="$errors->first('bond_note')">
                            <x-input id="bond_note" name="bond_note" :value="old('bond_note', $record->bond_note)" />
                        </x-field>

                        <x-field label="Internal Notes" for="internal_notes" class="sm:col-span-2"
                                 hint="Staff only. Never rendered on any public page."
                                 :error="$errors->first('internal_notes')">
                            <textarea id="internal_notes" name="internal_notes" rows="5"
                                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('internal_notes', $record->internal_notes) }}</textarea>
                        </x-field>
                    </div>
                </x-tab-panel>

                {{-- ------------------------------------------- Photograph --}}
                <x-tab-panel name="photo">
                    <div class="space-y-5 pb-6">
                        @unless ($mugshotPolicyOn)
                            <x-alert type="info" title="Mugshot Publication Is Turned Off">
                                Booking photographs uploaded here are stored and visible to staff, but they are not shown
                                anywhere on the public site while the module's mugshot policy is off. Several states
                                restrict or prohibit publishing them.
                            </x-alert>
                        @endunless

                        @if ($record->mugshot_path)
                            <div class="flex flex-wrap items-start gap-5">
                                <img src="{{ municipal_upload_url($record->mugshot_path) }}" alt="Current booking photograph"
                                     class="h-40 w-32 rounded-xl object-cover ring-1 ring-slate-200">
                                <div class="space-y-3">
                                    <x-toggle name="remove_mugshot" :checked="false"
                                              label="Remove This Photograph On Save"
                                              description="Deletes the file from the server." />
                                </div>
                            </div>
                        @endif

                        <x-field label="Upload A Booking Photograph" for="mugshot"
                                 hint="JPG or PNG, up to 6 MB." :error="$errors->first('mugshot')">
                            <input id="mugshot" name="mugshot" type="file" accept="image/*"
                                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                        </x-field>

                        <div class="section-divider pt-5 space-y-3">
                            <x-toggle name="mugshot_takedown_requested"
                                      :checked="old('mugshot_takedown_requested', $record->mugshot_takedown_requested ?? false)"
                                      label="A Takedown Of This Photograph Was Requested"
                                      description="Hides the photograph from the public site immediately, whatever the site-wide mugshot policy says. The rest of the record stays as it is." />

                            <x-field label="Takedown Note" for="mugshot_takedown_note"
                                     hint="Who asked, when, and on what grounds. Staff only."
                                     :error="$errors->first('mugshot_takedown_note')">
                                <x-input id="mugshot_takedown_note" name="mugshot_takedown_note"
                                         :value="old('mugshot_takedown_note', $record->mugshot_takedown_note)" />
                            </x-field>
                        </div>
                    </div>
                </x-tab-panel>

                {{-- ------------------------------------------ Publication --}}
                <x-tab-panel name="publication">
                    <div class="space-y-5 pb-6">
                        <div x-show="blocked" x-cloak>
                            <x-alert type="danger" title="Publication Is Blocked For This Record">
                                The subject is under {{ $minimumAge }}. Juvenile arrest records are not publishable on this platform
                                and this cannot be overridden in settings.
                            </x-alert>
                        </div>

                        <div x-show="! blocked">
                            <x-toggle name="is_published" :checked="old('is_published', $record->is_published ?? false)"
                                      label="Publish To The Public Blotter"
                                      description="Makes the name, charges, and disposition publicly visible." />
                        </div>

                        <div class="rounded-xl bg-slate-50 p-5 ring-1 ring-inset ring-slate-200">
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                                <x-icon name="clock" class="w-4 h-4 text-slate-500" aria-hidden="true" />
                                Retention
                            </h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Published records leave the public blotter {{ $retentionDays }} days after the booking date, automatically.
                                @if ($record->exists && $record->retention_expires_at)
                                    This record is scheduled to come off the blotter on
                                    {{ $record->retention_expires_at->format(config('municipal.date_format')) }}.
                                @endif
                            </p>
                            @if ($record->exists && $record->unpublish_reason)
                                <p class="mt-2 text-sm text-slate-500">Last unpublished because: {{ $record->unpublish_reason }}</p>
                            @endif
                        </div>

                    </div>
                </x-tab-panel>
            </x-tabs>
        </div>
    </x-card>
</div>

<script src="{{ asset_v('js/records.js') }}"></script>
