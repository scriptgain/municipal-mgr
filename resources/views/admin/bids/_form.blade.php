<x-card title="Opportunity Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Title" for="title" required class="sm:col-span-2" :error="$errors->first('title')">
            <x-input id="title" name="title" :value="old('title', $record->title)" required />
        </x-field>

        <x-field label="Reference Number" for="reference" :error="$errors->first('reference')">
            <x-input id="reference" name="reference" :value="old('reference', $record->reference)" placeholder="RFP 2026-07" />
        </x-field>

        <x-field label="Type" for="bid_type" :error="$errors->first('bid_type')">
            <x-select id="bid_type" name="bid_type">
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(old('bid_type', $record->bid_type ?: 'Bid') === $type)>{{ $type }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Department" for="department_id" :error="$errors->first('department_id')">
            <x-select id="department_id" name="department_id">
                <option value="">Not Department Specific</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id', $record->department_id) == $department->id)>{{ $department->name }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Status" for="status" :error="$errors->first('status')">
            <x-select id="status" name="status">
                <option value="open" @selected(old('status', $record->status ?: 'open') === 'open')>Open</option>
                <option value="closed" @selected(old('status', $record->status) === 'closed')>Closed</option>
                <option value="awarded" @selected(old('status', $record->status) === 'awarded')>Awarded</option>
                <option value="cancelled" @selected(old('status', $record->status) === 'cancelled')>Cancelled</option>
            </x-select>
        </x-field>

        <x-field label="Scope Of Work" for="description" class="sm:col-span-2" :error="$errors->first('description')">
            <textarea id="description" name="description" rows="10"
                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('description', $record->description) }}</textarea>
        </x-field>

        <x-field label="Opens" for="opens_at" :error="$errors->first('opens_at')">
            <x-input id="opens_at" name="opens_at" type="datetime-local" :value="old('opens_at', $record->opens_at?->format('Y-m-d\TH:i'))" />
        </x-field>

        <x-field label="Closes" for="closes_at" hint="Bids received after this time are not accepted." :error="$errors->first('closes_at')">
            <x-input id="closes_at" name="closes_at" type="datetime-local" :value="old('closes_at', $record->closes_at?->format('Y-m-d\TH:i'))" />
        </x-field>

        <x-field label="Pre-Bid Meeting" for="pre_bid_meeting_at" :error="$errors->first('pre_bid_meeting_at')">
            <x-input id="pre_bid_meeting_at" name="pre_bid_meeting_at" type="datetime-local" :value="old('pre_bid_meeting_at', $record->pre_bid_meeting_at?->format('Y-m-d\TH:i'))" />
        </x-field>

        <x-field label="Bid Package" for="document_id" :error="$errors->first('document_id')">
            <x-select id="document_id" name="document_id">
                <option value="">No Attached Package</option>
                @foreach ($documents as $document)
                    <option value="{{ $document->id }}" @selected(old('document_id', $record->document_id) == $document->id)>{{ $document->title }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Contact Name" for="contact_name" :error="$errors->first('contact_name')">
            <x-input id="contact_name" name="contact_name" :value="old('contact_name', $record->contact_name)" />
        </x-field>

        <x-field label="Contact Email" for="contact_email" :error="$errors->first('contact_email')">
            <x-input id="contact_email" name="contact_email" type="email" :value="old('contact_email', $record->contact_email)" />
        </x-field>

        <x-field label="Awarded To" for="awarded_to" class="sm:col-span-2" hint="Fill this in once the contract is awarded." :error="$errors->first('awarded_to')">
            <x-input id="awarded_to" name="awarded_to" :value="old('awarded_to', $record->awarded_to)" />
        </x-field>

        <div class="sm:col-span-2">
            <x-toggle name="is_published" :checked="old('is_published', $record->is_published ?? true)" label="Show On The Public Site" />
        </div>
    </div>
</x-card>
