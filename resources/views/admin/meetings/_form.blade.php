<x-card title="Meeting Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Public Body" for="body" required :error="$errors->first('body')">
            <input list="body-options" id="body" name="body" value="{{ old('body', $record->body) }}" required
                   class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
            <datalist id="body-options">
                @foreach ($bodies as $body)
                    <option value="{{ $body }}"></option>
                @endforeach
            </datalist>
        </x-field>

        <x-field label="Meeting Type" for="title" hint="Regular Meeting, Special Session, Work Study…" :error="$errors->first('title')">
            <x-input id="title" name="title" :value="old('title', $record->title)" placeholder="Regular Meeting" />
        </x-field>

        <x-field label="Date And Time" for="meets_at" required :error="$errors->first('meets_at')">
            <x-input id="meets_at" name="meets_at" type="datetime-local" required
                     :value="old('meets_at', $record->meets_at?->format('Y-m-d\TH:i'))" />
        </x-field>

        <x-field label="Status" for="status" :error="$errors->first('status')">
            <x-select id="status" name="status">
                <option value="scheduled" @selected(old('status', $record->status ?: 'scheduled') === 'scheduled')>Scheduled</option>
                <option value="held" @selected(old('status', $record->status) === 'held')>Held</option>
                <option value="cancelled" @selected(old('status', $record->status) === 'cancelled')>Cancelled</option>
            </x-select>
        </x-field>

        <x-field label="Location" for="location" :error="$errors->first('location')">
            <x-input id="location" name="location" :value="old('location', $record->location)" placeholder="Council Chambers" />
        </x-field>

        <x-field label="Address" for="address" :error="$errors->first('address')">
            <x-input id="address" name="address" :value="old('address', $record->address)" />
        </x-field>

        <x-field label="Summary" for="summary" class="sm:col-span-2" hint="Optional note shown with the listing." :error="$errors->first('summary')">
            <textarea id="summary" name="summary" rows="4"
                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('summary', $record->summary) }}</textarea>
        </x-field>
    </div>
</x-card>

<x-card title="Documents And Video" subtitle="Link files already uploaded to the document library.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Agenda" for="agenda_document_id" :error="$errors->first('agenda_document_id')">
            <x-select id="agenda_document_id" name="agenda_document_id">
                <option value="">Not Posted Yet</option>
                @foreach ($documents as $document)
                    <option value="{{ $document->id }}" @selected(old('agenda_document_id', $record->agenda_document_id) == $document->id)>{{ $document->title }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Minutes" for="minutes_document_id" :error="$errors->first('minutes_document_id')">
            <x-select id="minutes_document_id" name="minutes_document_id">
                <option value="">Not Approved Yet</option>
                @foreach ($documents as $document)
                    <option value="{{ $document->id }}" @selected(old('minutes_document_id', $record->minutes_document_id) == $document->id)>{{ $document->title }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Meeting Packet" for="packet_document_id" :error="$errors->first('packet_document_id')">
            <x-select id="packet_document_id" name="packet_document_id">
                <option value="">No Packet</option>
                @foreach ($documents as $document)
                    <option value="{{ $document->id }}" @selected(old('packet_document_id', $record->packet_document_id) == $document->id)>{{ $document->title }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Video Recording Link" for="video_url" :error="$errors->first('video_url')">
            <x-input id="video_url" name="video_url" type="url" :value="old('video_url', $record->video_url)" />
        </x-field>

        <div class="min-w-0 sm:col-span-2">
            <x-toggle name="is_published" :checked="old('is_published', $record->is_published ?? true)"
                      label="Show On The Public Site" />
        </div>
    </div>
</x-card>

<x-admin.seo-panel :record="$record" kind="Meeting" />
