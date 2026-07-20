<x-layouts.app :title="'Edit Record ' . $record->reference()">
    <x-page-header :title="$record->listName()" icon="shield"
                   :subtitle="'Booked ' . $record->booked_at?->format(config('municipal.date_format')) . ' by ' . $record->arresting_agency">
        <x-slot:actions>
            @if ($record->isPubliclyVisible())
                <x-button variant="secondary" icon="eye" :href="route('site.records.show', $record->public_ref)">View Public Record</x-button>
            @endif
            <x-button variant="secondary" icon="chevron-left" :href="route('arrest-records.index')">Back To List</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('arrest-records.update', $record->public_ref) }}" class="space-y-6" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('admin.arrest-records._form')

        <div class="section-divider pt-5 flex flex-wrap items-center justify-end gap-3">
            <x-button variant="secondary" :href="route('arrest-records.index')">Cancel</x-button>
            <x-button type="submit" icon="check">Save Changes</x-button>
        </div>
    </form>

    {{-- Removal actions sit OUTSIDE the edit form on purpose: each one posts its
         own form from inside a modal, and a form nested in another form is
         dropped by the browser. Keeping them here also keeps a destructive
         action from sharing a save button with a typo fix. --}}
    <div class="section-divider mt-8 pt-8">
        <x-card title="Removing This Record"
                subtitle="Two different actions for two different reasons. Choose the one that matches why the record is going away.">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="rounded-xl bg-white p-5 ring-1 ring-inset ring-amber-200">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 ring-1 ring-amber-200">
                            <x-icon name="scale" class="w-5 h-5" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <h3 class="font-semibold text-slate-900">Expunge Under A Court Order</h3>
                            <p class="mt-1 text-sm leading-relaxed text-slate-600">
                                A court sealed or expunged this case. Destroys the record, its charges, and the booking
                                photograph, and writes a compliance entry recording who ordered the removal.
                            </p>
                            <div class="mt-4">
                                <x-records.expunge-button :name="'expunge-edit-' . $record->id"
                                                          :action="route('arrest-records.expunge', $record->public_ref)"
                                                          :reference="$record->reference()"
                                                          trigger="button" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 ring-1 ring-inset ring-rose-200">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600 ring-1 ring-rose-200">
                            <x-icon name="trash" class="w-5 h-5" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <h3 class="font-semibold text-slate-900">Delete The Record</h3>
                            <p class="mt-1 text-sm leading-relaxed text-slate-600">
                                The record was entered in error or duplicated. Removes it and its booking photograph.
                                Use Expunge instead when a court has ordered the removal.
                            </p>
                            <div class="mt-4">
                                <x-delete-button :name="'delete-record-' . $record->id"
                                                 :action="route('arrest-records.destroy', $record->public_ref)"
                                                 title="Delete This Record?"
                                                 message="This removes the record, its charges, and its booking photograph. If a court ordered this case sealed or expunged, cancel and use Expunge instead so the removal is logged as compliance with the order." />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>
</x-layouts.app>
