<x-card title="Directory Entry">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Full Name" for="name" required :error="$errors->first('name')">
            <x-input id="name" name="name" :value="old('name', $record->name)" required />
        </x-field>

        <x-field label="Job Title" for="job_title" required :error="$errors->first('job_title')">
            <x-input id="job_title" name="job_title" :value="old('job_title', $record->job_title)" required />
        </x-field>

        <x-field label="Department" for="department_id" :error="$errors->first('department_id')">
            <x-select id="department_id" name="department_id">
                <option value="">No Department</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id', $record->department_id) == $department->id)>{{ $department->name }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Office" for="office" :error="$errors->first('office')">
            <x-input id="office" name="office" :value="old('office', $record->office)" placeholder="Village Hall, Room 2" />
        </x-field>

        <x-field label="Email" for="email" :error="$errors->first('email')">
            <x-input id="email" name="email" type="email" :value="old('email', $record->email)" />
        </x-field>

        <div class="grid gap-5 sm:grid-cols-3">
            <x-field label="Phone" for="phone" class="sm:col-span-2" :error="$errors->first('phone')">
                <x-input id="phone" name="phone" :value="old('phone', $record->phone)" />
            </x-field>
            <x-field label="Extension" for="extension" :error="$errors->first('extension')">
                <x-input id="extension" name="extension" :value="old('extension', $record->extension)" />
            </x-field>
        </div>

        <x-field label="Biography" for="bio" class="sm:col-span-2" :error="$errors->first('bio')">
            <textarea id="bio" name="bio" rows="6"
                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('bio', $record->bio) }}</textarea>
        </x-field>

        <x-field label="Photograph" for="photo" :error="$errors->first('photo')">
            <input id="photo" name="photo" type="file" accept="image/*"
                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
            @if ($record->photo_path)
                <img src="{{ municipal_upload_url($record->photo_path) }}" alt="Current photograph" class="mt-3 h-20 w-20 rounded-full object-cover ring-1 ring-slate-200">
            @endif
        </x-field>

        <div class="space-y-4">
            <x-field label="Sort Order" for="sort_order" :error="$errors->first('sort_order')">
                <x-input id="sort_order" name="sort_order" type="number" min="0" :value="old('sort_order', $record->sort_order ?? 0)" />
            </x-field>
            <x-toggle name="is_published" :checked="old('is_published', $record->is_published ?? true)"
                      label="List In The Public Directory" />
        </div>
    </div>
</x-card>
