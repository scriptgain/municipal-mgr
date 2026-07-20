<x-card title="Department Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Department Name" for="name" required :error="$errors->first('name')">
            <x-input id="name" name="name" :value="old('name', $record->name)" required />
        </x-field>

        <x-field label="Icon" for="icon" :error="$errors->first('icon')">
            <x-select id="icon" name="icon">
                @foreach ($icons as $icon)
                    <option value="{{ $icon }}" @selected(old('icon', $record->icon ?: 'building') === $icon)>{{ \Illuminate\Support\Str::headline($icon) }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="One Line Summary" for="summary" class="sm:col-span-2" hint="Shown on the departments index cards." :error="$errors->first('summary')">
            <x-input id="summary" name="summary" :value="old('summary', $record->summary)" />
        </x-field>

        <x-field label="About This Department" for="description" class="sm:col-span-2" :error="$errors->first('description')">
            <textarea id="description" name="description" rows="8"
                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('description', $record->description) }}</textarea>
        </x-field>

        <x-field label="Department Head" for="head_staff_id" :error="$errors->first('head_staff_id')">
            <x-select id="head_staff_id" name="head_staff_id">
                <option value="">Not Set</option>
                @foreach ($staff as $member)
                    <option value="{{ $member->id }}" @selected(old('head_staff_id', $record->head_staff_id) == $member->id)>{{ $member->name }} – {{ $member->job_title }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Sort Order" for="sort_order" :error="$errors->first('sort_order')">
            <x-input id="sort_order" name="sort_order" type="number" min="0" :value="old('sort_order', $record->sort_order ?? 0)" />
        </x-field>
    </div>
</x-card>

<x-card title="Contact Information" subtitle="Shown on the department page and in the footer.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Phone" for="phone" :error="$errors->first('phone')">
            <x-input id="phone" name="phone" :value="old('phone', $record->phone)" />
        </x-field>
        <x-field label="Fax" for="fax" :error="$errors->first('fax')">
            <x-input id="fax" name="fax" :value="old('fax', $record->fax)" />
        </x-field>
        <x-field label="Email" for="email" :error="$errors->first('email')">
            <x-input id="email" name="email" type="email" :value="old('email', $record->email)" />
        </x-field>
        <x-field label="Office Hours" for="hours" :error="$errors->first('hours')">
            <x-input id="hours" name="hours" :value="old('hours', $record->hours)" placeholder="Monday to Friday, 8:00 AM to 5:00 PM" />
        </x-field>
        <x-field label="Address" for="address" class="sm:col-span-2" :error="$errors->first('address')">
            <x-input id="address" name="address" :value="old('address', $record->address)" />
        </x-field>
        <div class="sm:col-span-2">
            <x-toggle name="is_published" :checked="old('is_published', $record->is_published ?? true)"
                      label="Show On The Public Site" />
        </div>
    </div>
</x-card>
