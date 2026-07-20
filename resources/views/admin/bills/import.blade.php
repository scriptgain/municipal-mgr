<x-layouts.app title="Import Bills">
    <x-page-header title="Import Bills" icon="upload"
                   subtitle="Load a billing run from a CSV file exported by your utility billing or finance system.">
        <x-slot:actions>
            <x-button variant="secondary" icon="chevron-left" :href="route('bills.index')">Back To Bills</x-button>
        </x-slot:actions>
    </x-page-header>

    @error('file')
        <div class="mb-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-rose-200">{{ $message }}</div>
    @enderror

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2" title="Upload A CSV File">
            <form method="POST" action="{{ route('bills.import.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <x-field label="Bill Type" for="bill_type_id" required
                         hint="Every bill in this file is created as this type."
                         :error="$errors->first('bill_type_id')">
                    <x-select id="bill_type_id" name="bill_type_id" required>
                        <option value="">Choose A Type</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}">{{ $type->label }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="CSV File" for="file" required
                         hint="Up to 5 MB. The first row must be a header row."
                         :error="$errors->first('file')">
                    <input id="file" name="file" type="file" accept=".csv,text/csv" required
                           class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand-700 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                </x-field>

                <div class="section-divider pt-5 flex items-center justify-end gap-2">
                    <x-button variant="secondary" :href="route('bills.index')">Cancel</x-button>
                    <x-button type="submit" icon="upload">Import Bills</x-button>
                </div>
            </form>
        </x-card>

        <x-card title="File Format" subtitle="Column headers are matched by name, in any order.">
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="font-medium text-slate-900">amount <span class="text-rose-500">required</span></dt>
                    <dd class="text-slate-500">The amount due, for example 124.50</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-900">account_number</dt>
                    <dd class="text-slate-500">Utility account or citation number</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-900">name</dt>
                    <dd class="text-slate-500">Who the bill is addressed to</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-900">email</dt>
                    <dd class="text-slate-500">Used for the receipt, and to link the bill to a resident record</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-900">phone</dt>
                    <dd class="text-slate-500">Optional</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-900">last_name</dt>
                    <dd class="text-slate-500">Second factor for online lookup. Taken from <span class="font-mono text-xs">name</span> if absent</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-900">zip</dt>
                    <dd class="text-slate-500">Second factor for online lookup. Recommended</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-900">description</dt>
                    <dd class="text-slate-500">Shown to the resident, for example "Water and sewer, March 2026"</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-900">due_date</dt>
                    <dd class="text-slate-500">Any recognisable date format</dd>
                </div>
            </dl>

            <div class="section-divider mt-5 pt-5">
                <p class="text-sm text-slate-600">
                    Reference numbers are generated automatically. Rows with a missing or unreadable amount are
                    skipped and reported back to you by line number rather than being silently dropped.
                </p>
            </div>
        </x-card>
    </div>
</x-layouts.app>
