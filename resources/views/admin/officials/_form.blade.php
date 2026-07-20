<x-card title="Official Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Full Name" for="name" required :error="$errors->first('name')">
            <x-input id="name" name="name" :value="old('name', $record->name)" required />
        </x-field>

        <x-field label="Office Held" for="office" required :error="$errors->first('office')">
            <input list="office-options" id="office" name="office" value="{{ old('office', $record->office) }}" required
                   class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
            <datalist id="office-options">
                @foreach ($offices as $office)
                    <option value="{{ $office }}"></option>
                @endforeach
            </datalist>
        </x-field>

        <x-field label="District Or Ward" for="district" :error="$errors->first('district')">
            <x-input id="district" name="district" :value="old('district', $record->district)" placeholder="Ward 3, At Large" />
        </x-field>

        <x-field label="Sort Order" for="sort_order" hint="The mayor is usually 0." :error="$errors->first('sort_order')">
            <x-input id="sort_order" name="sort_order" type="number" min="0" :value="old('sort_order', $record->sort_order ?? 0)" />
        </x-field>

        <x-field label="Term Start" for="term_start" :error="$errors->first('term_start')">
            <x-input id="term_start" name="term_start" type="date" :value="old('term_start', $record->term_start?->format('Y-m-d'))" />
        </x-field>

        <x-field label="Term End" for="term_end" :error="$errors->first('term_end')">
            <x-input id="term_end" name="term_end" type="date" :value="old('term_end', $record->term_end?->format('Y-m-d'))" />
        </x-field>

        <x-field label="Email" for="email" :error="$errors->first('email')">
            <x-input id="email" name="email" type="email" :value="old('email', $record->email)" />
        </x-field>

        <x-field label="Phone" for="phone" :error="$errors->first('phone')">
            <x-input id="phone" name="phone" :value="old('phone', $record->phone)" />
        </x-field>

        <x-field label="Biography" for="bio" class="sm:col-span-2" :error="$errors->first('bio')">
            <textarea id="bio" name="bio" rows="8"
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
            <x-toggle name="is_current" :checked="old('is_current', $record->is_current ?? true)"
                      label="Currently In Office"
                      description="Former officials move to the historical roster." />
            <x-toggle name="is_published" :checked="old('is_published', $record->is_published ?? true)"
                      label="Show On The Public Site" />
        </div>
    </div>
</x-card>
