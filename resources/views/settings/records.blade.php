<x-layouts.app title="Arrest Records">
    <x-page-header title="Jail And Arrest Records" icon="shield"
                   subtitle="An optional module. It is switched off until someone here decides otherwise." />

    @if ($enabled)
        <x-alert type="success" title="The Module Is Enabled" class="mb-6">
            The public blotter is live at <span class="font-semibold">{{ route('site.records.blotter') }}</span> and
            {{ $publishedCount }} record(s) are currently visible to the public.
        </x-alert>
    @else
        <x-alert type="warn" title="The Module Is Disabled" class="mb-6">
            The public blotter and inmate roster return a not-found page, nothing appears in the site navigation, and no
            arrest data is reachable by anyone outside this panel. Nothing on this page takes effect until you enable it.
        </x-alert>
    @endif

    <form method="POST" action="{{ route('settings.records.update') }}" class="space-y-6">
        @csrf @method('PUT')

        <x-tabs :tabs="[
            'module' => ['label' => 'Enable The Module', 'icon' => 'shield'],
            'guardrails' => ['label' => 'Guardrails', 'icon' => 'scale'],
            'disclaimer' => ['label' => 'Disclaimer And Contact', 'icon' => 'info'],
        ]">

            {{-- --------------------------------------------- Enable --}}
            <x-tab-panel name="module">
                <x-card title="Before You Turn This On"
                        subtitle="Publishing arrest records is a decision with consequences for people who have not been convicted of anything.">
                    <div class="space-y-5">
                        <div class="rounded-xl bg-amber-50 p-5 ring-1 ring-inset ring-amber-200">
                            <h3 class="flex items-center gap-2 font-semibold text-amber-950">
                                <x-icon name="scale" class="w-5 h-5 text-amber-600" aria-hidden="true" />
                                What You Are Taking On
                            </h3>
                            <ul class="mt-3 space-y-2 text-sm leading-relaxed text-amber-900">
                                <li>Everyone listed on a blotter is presumed innocent. Most will never be convicted of what they were booked for.</li>
                                <li>A booking entry published without its outcome follows a person through search results for years, long after a case is dropped.</li>
                                <li>Publishing booking photographs is restricted or prohibited in several states. Confirm your own law before enabling it.</li>
                                <li>Courts issue sealing and expungement orders, and you have a legal duty to comply promptly when one arrives.</li>
                                <li>Juvenile records are never publishable on this platform. That is not adjustable here or anywhere else.</li>
                            </ul>
                        </div>

                        <div class="section-divider pt-5">
                            <x-toggle name="records_module_enabled" :checked="$enabled"
                                      label="Enable Jail And Arrest Records"
                                      description="Turns on the public blotter, the inmate roster, and the staff screens, and adds a menu item under an existing navigation dropdown." />
                        </div>

                        <x-field label="Publishing Agency" for="records_agency_name"
                                 hint="Shown above the public listings. For example: Cottonwood Springs Police Department."
                                 :error="$errors->first('records_agency_name')">
                            <x-input id="records_agency_name" name="records_agency_name"
                                     :value="old('records_agency_name', $settings['records_agency_name'])" />
                        </x-field>
                    </div>
                </x-card>
            </x-tab-panel>

            {{-- ----------------------------------------- Guardrails --}}
            <x-tab-panel name="guardrails">
                <div class="space-y-6">
                    <x-card title="Booking Photographs"
                            subtitle="Off by default. Several states restrict or ban publication of mugshots.">
                        <div class="space-y-4">
                            <x-toggle name="records_mugshots_enabled" :checked="$settings['records_mugshots_enabled'] === '1'"
                                      label="Show Booking Photographs On The Public Site"
                                      description="Photographs can always be stored and viewed by staff. This controls only whether the public sees them." />
                            <p class="text-sm text-slate-500">
                                Individual records can also be flagged for takedown, which hides that photograph whatever this setting says.
                            </p>
                        </div>
                    </x-card>

                    <x-card title="Retention"
                            subtitle="How long a published record stays on the public blotter, counted from the booking date.">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-field label="Retention Window In Days" for="records_retention_days" required
                                     hint="60 days is the conservative default. Records are kept for staff after they leave the public site."
                                     :error="$errors->first('records_retention_days')">
                                <x-input id="records_retention_days" name="records_retention_days" type="number" min="1" max="3650"
                                         :value="old('records_retention_days', $settings['records_retention_days'])" required />
                            </x-field>
                            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-200">
                                <p class="text-sm leading-relaxed text-slate-600">
                                    Expiry is enforced on every public read and again nightly, when records past their window
                                    are unpublished and the change is written to the audit log.
                                </p>
                            </div>
                        </div>
                    </x-card>

                    <x-card title="What The Public Sees"
                            subtitle="Fields that are reasonable to withhold without making the blotter useless.">
                        <div class="space-y-4">
                            <x-toggle name="records_roster_enabled" :checked="$settings['records_roster_enabled'] === '1'"
                                      label="Publish The Inmate Roster"
                                      description="A current custody list, derived from records still marked In Custody." />
                            <x-toggle name="records_show_bond" :checked="$settings['records_show_bond'] === '1'"
                                      label="Show Bond Amounts" />
                            <x-toggle name="records_show_case_number" :checked="$settings['records_show_case_number'] === '1'"
                                      label="Show Case Numbers" />
                            <x-toggle name="records_public_search_enabled" :checked="$settings['records_public_search_enabled'] === '1'"
                                      label="Allow The Public To Search By Name"
                                      description="Turning this off leaves date browsing in place but stops the blotter working as a name lookup service." />
                        </div>
                    </x-card>

                    <x-card title="Juvenile Records">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 ring-1 ring-slate-200">
                                <x-icon name="lock" class="w-5 h-5" aria-hidden="true" />
                            </span>
                            <p class="text-sm leading-relaxed text-slate-600">
                                A subject under {{ $minimumAge }} can never be published, and there is deliberately no switch here to change that.
                                Such records can still be created and kept for staff use; publication is refused at save time, on the
                                publish action, and again on every public read.
                            </p>
                        </div>
                    </x-card>
                </div>
            </x-tab-panel>

            {{-- ----------------------------------------- Disclaimer --}}
            <x-tab-panel name="disclaimer">
                <div class="space-y-6">
                    <x-card title="Standing Disclaimer"
                            subtitle="Shown at the top of every public arrest-records page. It cannot be removed, only reworded.">
                        <div class="space-y-4">
                            <x-field label="Disclaimer Text" for="records_disclaimer" required
                                     :error="$errors->first('records_disclaimer')">
                                <textarea id="records_disclaimer" name="records_disclaimer" rows="5" required
                                          class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('records_disclaimer', $settings['records_disclaimer']) }}</textarea>
                            </x-field>

                            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-200">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Suggested Wording</p>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $defaultDisclaimer }}</p>
                            </div>
                        </div>
                    </x-card>

                    <x-card title="Introduction And Contact">
                        <div class="space-y-5">
                            <x-field label="Blotter Introduction" for="records_blotter_intro"
                                     hint="Optional. Appears beneath the disclaimer on the blotter, for local context such as which agencies report here."
                                     :error="$errors->first('records_blotter_intro')">
                                <textarea id="records_blotter_intro" name="records_blotter_intro" rows="4"
                                          class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('records_blotter_intro', $settings['records_blotter_intro']) }}</textarea>
                            </x-field>

                            <x-field label="Corrections And Removal Contact" for="records_takedown_contact"
                                     hint="How someone reaches you to correct a record, request a photograph takedown, or serve a court order. Publish a real route."
                                     :error="$errors->first('records_takedown_contact')">
                                <x-input id="records_takedown_contact" name="records_takedown_contact"
                                         :value="old('records_takedown_contact', $settings['records_takedown_contact'])"
                                         placeholder="Records Division, (928) 555-0140, records@example.gov" />
                            </x-field>
                        </div>
                    </x-card>
                </div>
            </x-tab-panel>
        </x-tabs>

        <div class="section-divider pt-5 flex items-center justify-end gap-2">
            <x-button type="submit" icon="check">Save Settings</x-button>
        </div>
    </form>
</x-layouts.app>
