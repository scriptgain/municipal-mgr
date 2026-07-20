<x-card title="Notice Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Notice Title" for="title" required class="sm:col-span-2" :error="$errors->first('title')">
            <x-input id="title" name="title" :value="old('title', $record->title)" required />
        </x-field>

        <x-field label="Notice Type" for="notice_type" :error="$errors->first('notice_type')">
            <x-select id="notice_type" name="notice_type">
                @foreach ($noticeTypes as $type)
                    <option value="{{ $type }}" @selected(old('notice_type', $record->notice_type ?: 'General') === $type)>{{ $type }}</option>
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

        <x-field label="Notice Text" for="body" class="sm:col-span-2" hint="The full legal text as posted." :error="$errors->first('body')">
            <textarea id="body" name="body" rows="12"
                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('body', $record->body) }}</textarea>
        </x-field>

        <x-field label="Posting Date" for="posted_at" hint="When the notice legally goes up." :error="$errors->first('posted_at')">
            <x-input id="posted_at" name="posted_at" type="datetime-local"
                     :value="old('posted_at', $record->posted_at?->format('Y-m-d\TH:i'))" />
        </x-field>

        <x-field label="Expiry Date" for="expires_at" hint="Leave blank for a notice that does not expire." :error="$errors->first('expires_at')">
            <x-input id="expires_at" name="expires_at" type="datetime-local"
                     :value="old('expires_at', $record->expires_at?->format('Y-m-d\TH:i'))" />
        </x-field>

        <x-field label="Attached Document" for="document_id" hint="Link the signed PDF from the document library." :error="$errors->first('document_id')">
            <x-select id="document_id" name="document_id">
                <option value="">No Attachment</option>
                @foreach ($documents as $document)
                    <option value="{{ $document->id }}" @selected(old('document_id', $record->document_id) == $document->id)>{{ $document->title }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Status" for="status" :error="$errors->first('status')">
            <x-select id="status" name="status">
                <option value="draft" @selected(old('status', $record->status ?: 'published') === 'draft')>Draft (Staff Only)</option>
                <option value="published" @selected(old('status', $record->status ?: 'published') === 'published')>Published (Public)</option>
            </x-select>
        </x-field>
    </div>
</x-card>
