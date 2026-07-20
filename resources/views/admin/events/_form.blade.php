<x-card title="Event Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Event Title" for="title" required class="sm:col-span-2" :error="$errors->first('title')">
            <x-input id="title" name="title" :value="old('title', $record->title)" required />
        </x-field>

        <x-field label="Starts" for="starts_at" required :error="$errors->first('starts_at')">
            <x-input id="starts_at" name="starts_at" type="datetime-local" required
                     :value="old('starts_at', $record->starts_at?->format('Y-m-d\TH:i'))" />
        </x-field>

        <x-field label="Ends" for="ends_at" hint="Leave blank for an open-ended event." :error="$errors->first('ends_at')">
            <x-input id="ends_at" name="ends_at" type="datetime-local"
                     :value="old('ends_at', $record->ends_at?->format('Y-m-d\TH:i'))" />
        </x-field>

        <x-field label="Category" for="category" :error="$errors->first('category')">
            <x-select id="category" name="category">
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected(old('category', $record->category ?: 'Community') === $category)>{{ $category }}</option>
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

        <x-field label="Location Name" for="location" :error="$errors->first('location')">
            <x-input id="location" name="location" :value="old('location', $record->location)" placeholder="Village Hall, Council Chambers" />
        </x-field>

        <x-field label="Street Address" for="address" :error="$errors->first('address')">
            <x-input id="address" name="address" :value="old('address', $record->address)" />
        </x-field>

        <x-field label="Description" for="description" class="sm:col-span-2" :error="$errors->first('description')">
            <textarea id="description" name="description" rows="8"
                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('description', $record->description) }}</textarea>
        </x-field>

        <x-field label="Registration Link" for="registration_url" :error="$errors->first('registration_url')">
            <x-input id="registration_url" name="registration_url" type="url" :value="old('registration_url', $record->registration_url)" />
        </x-field>

        <x-field label="Event Image" for="image" :error="$errors->first('image')">
            <input id="image" name="image" type="file" accept="image/*"
                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
        </x-field>

        <div class="min-w-0 sm:col-span-2 space-y-4">
            <x-toggle name="all_day" :checked="old('all_day', $record->all_day)" label="All Day Event"
                      description="Hides the start and end times on the public calendar." />
            <x-toggle name="is_published" :checked="old('is_published', $record->is_published ?? true)" label="Publish On The Public Calendar" />
        </div>
    </div>
</x-card>

<x-admin.seo-panel :record="$record" kind="Event" />
