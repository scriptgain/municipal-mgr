<x-card title="Document Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Document Title" for="title" required class="sm:col-span-2" :error="$errors->first('title')">
            <x-input id="title" name="title" :value="old('title', $record->title)" required />
        </x-field>

        <x-field label="File" for="file" :required="! $record->exists" class="sm:col-span-2"
                 hint="PDF, Office documents, images, or a ZIP archive. Up to 50 MB." :error="$errors->first('file')">
            <input id="file" name="file" type="file" @if (! $record->exists) required @endif
                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
            @if ($record->exists && $record->file_name)
                <p class="mt-2 text-sm text-slate-500">
                    Currently: <span class="font-medium text-slate-700">{{ $record->file_name }}</span> ({{ $record->sizeDisplay() }}).
                    Uploading a new file replaces it.
                </p>
            @endif
        </x-field>

        <x-field label="Category" for="document_category_id" :error="$errors->first('document_category_id')">
            <x-select id="document_category_id" name="document_category_id">
                <option value="">Uncategorised</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('document_category_id', $record->document_category_id) == $category->id)>{{ $category->name }}</option>
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

        <x-field label="Reference Number" for="reference" hint="Ordinance 2026-14, Resolution 88…" :error="$errors->first('reference')">
            <x-input id="reference" name="reference" :value="old('reference', $record->reference)" />
        </x-field>

        <x-field label="Document Date" for="document_date" hint="The date on the document, not the upload date." :error="$errors->first('document_date')">
            <x-input id="document_date" name="document_date" type="date" :value="old('document_date', $record->document_date?->format('Y-m-d'))" />
        </x-field>

        <x-field label="Description" for="description" class="sm:col-span-2" :error="$errors->first('description')">
            <textarea id="description" name="description" rows="4"
                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('description', $record->description) }}</textarea>
        </x-field>

        <x-field label="Search Keywords" for="keywords" class="sm:col-span-2"
                 hint="Extra terms residents might search for. Comma separated." :error="$errors->first('keywords')">
            <x-input id="keywords" name="keywords" :value="old('keywords', $record->keywords)" placeholder="zoning, variance, setback" />
        </x-field>

        <div class="sm:col-span-2">
            <x-toggle name="is_published" :checked="old('is_published', $record->is_published ?? true)"
                      label="Publish In The Public Library"
                      description="Unpublishing also blocks the direct download link." />
        </div>
    </div>
</x-card>
