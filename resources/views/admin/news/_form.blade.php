<x-card title="Post Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Headline" for="title" required class="sm:col-span-2" :error="$errors->first('title')">
            <x-input id="title" name="title" :value="old('title', $record->title)" required />
        </x-field>

        <x-field label="Category" for="category" :error="$errors->first('category')">
            <x-select id="category" name="category">
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected(old('category', $record->category ?: 'News') === $category)>{{ $category }}</option>
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

        <x-field label="Summary" for="excerpt" hint="Shown on cards and in the news list. Keep it to two sentences." class="sm:col-span-2" :error="$errors->first('excerpt')">
            <textarea id="excerpt" name="excerpt" rows="2"
                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('excerpt', $record->excerpt) }}</textarea>
        </x-field>

        <x-field label="Full Story" for="body" hint="Basic HTML is allowed." class="sm:col-span-2" :error="$errors->first('body')">
            <textarea id="body" name="body" rows="12"
                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('body', $record->body) }}</textarea>
        </x-field>

        <x-field label="Featured Image" for="image" hint="Optional. Shown at the top of the story." :error="$errors->first('image')">
            <input id="image" name="image" type="file" accept="image/*"
                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
            @if ($record->image_path)
                <img src="{{ municipal_upload_url($record->image_path) }}" alt="Current featured image" class="mt-3 h-24 rounded-lg object-cover ring-1 ring-slate-200">
            @endif
        </x-field>

        <div class="space-y-4">
            <x-field label="Status" for="status" :error="$errors->first('status')">
                <x-select id="status" name="status">
                    <option value="draft" @selected(old('status', $record->status ?: 'draft') === 'draft')>Draft (Staff Only)</option>
                    <option value="published" @selected(old('status', $record->status) === 'published')>Published (Public)</option>
                </x-select>
            </x-field>
            <x-field label="Publish Date" for="published_at" hint="Leave blank to publish immediately." :error="$errors->first('published_at')">
                <x-input id="published_at" name="published_at" type="datetime-local"
                         :value="old('published_at', $record->published_at?->format('Y-m-d\TH:i'))" />
            </x-field>
            <x-toggle name="is_featured" :checked="old('is_featured', $record->is_featured)"
                      label="Feature On The Homepage"
                      description="Pins this story to the top of the homepage." />
        </div>
    </div>
</x-card>
