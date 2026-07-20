<x-card title="Category Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Category Name" for="name" required :error="$errors->first('name')">
            <x-input id="name" name="name" :value="old('name', $record->name)" required />
        </x-field>

        <x-field label="Icon" for="icon" :error="$errors->first('icon')">
            <x-select id="icon" name="icon">
                @foreach ($icons as $icon)
                    <option value="{{ $icon }}" @selected(old('icon', $record->icon ?: 'folder') === $icon)>{{ \Illuminate\Support\Str::headline($icon) }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Description" for="description" class="sm:col-span-2" :error="$errors->first('description')">
            <x-input id="description" name="description" :value="old('description', $record->description)" />
        </x-field>

        <x-field label="Sort Order" for="sort_order" :error="$errors->first('sort_order')">
            <x-input id="sort_order" name="sort_order" type="number" min="0" :value="old('sort_order', $record->sort_order ?? 0)" />
        </x-field>
    </div>
</x-card>
