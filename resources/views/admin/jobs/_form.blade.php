<x-card title="Position Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Position Title" for="title" required class="sm:col-span-2" :error="$errors->first('title')">
            <x-input id="title" name="title" :value="old('title', $record->title)" required />
        </x-field>

        <x-field label="Department" for="department_id" :error="$errors->first('department_id')">
            <x-select id="department_id" name="department_id">
                <option value="">Not Department Specific</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id', $record->department_id) == $department->id)>{{ $department->name }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Employment Type" for="employment_type" :error="$errors->first('employment_type')">
            <x-select id="employment_type" name="employment_type">
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(old('employment_type', $record->employment_type ?: 'Full Time') === $type)>{{ $type }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Salary Range" for="salary_range" :error="$errors->first('salary_range')">
            <x-input id="salary_range" name="salary_range" :value="old('salary_range', $record->salary_range)" placeholder="$52,000 to $64,000 Annually" />
        </x-field>

        <x-field label="Closing Date" for="closes_at" :error="$errors->first('closes_at')">
            <x-input id="closes_at" name="closes_at" type="datetime-local"
                     :value="old('closes_at', $record->closes_at?->format('Y-m-d\TH:i'))" />
        </x-field>

        <x-field label="Position Summary" for="description" class="sm:col-span-2" :error="$errors->first('description')">
            <textarea id="description" name="description" rows="10"
                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('description', $record->description) }}</textarea>
        </x-field>

        <x-field label="Requirements And Qualifications" for="requirements" class="sm:col-span-2" :error="$errors->first('requirements')">
            <textarea id="requirements" name="requirements" rows="8"
                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('requirements', $record->requirements) }}</textarea>
        </x-field>
    </div>
</x-card>

<x-card title="How To Apply">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Application Link" for="apply_url" :error="$errors->first('apply_url')">
            <x-input id="apply_url" name="apply_url" type="url" :value="old('apply_url', $record->apply_url)" />
        </x-field>

        <x-field label="Application Email" for="apply_email" :error="$errors->first('apply_email')">
            <x-input id="apply_email" name="apply_email" type="email" :value="old('apply_email', $record->apply_email)" />
        </x-field>

        <x-field label="Application Form" for="application_document_id" hint="A downloadable form from the document library." :error="$errors->first('application_document_id')">
            <x-select id="application_document_id" name="application_document_id">
                <option value="">No Downloadable Form</option>
                @foreach ($documents as $document)
                    <option value="{{ $document->id }}" @selected(old('application_document_id', $record->application_document_id) == $document->id)>{{ $document->title }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Status" for="status" :error="$errors->first('status')">
            <x-select id="status" name="status">
                <option value="draft" @selected(old('status', $record->status ?: 'published') === 'draft')>Draft</option>
                <option value="published" @selected(old('status', $record->status ?: 'published') === 'published')>Published</option>
                <option value="closed" @selected(old('status', $record->status) === 'closed')>Closed</option>
            </x-select>
        </x-field>

        <div class="sm:col-span-2">
            <x-toggle name="is_open_until_filled" :checked="old('is_open_until_filled', $record->is_open_until_filled)"
                      label="Open Until Filled"
                      description="Ignores the closing date and keeps the posting live." />
        </div>
    </div>
</x-card>
