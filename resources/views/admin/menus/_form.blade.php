<x-card title="Menu Item">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Label" for="label" required :error="$errors->first('label')">
            <x-input id="label" name="label" :value="old('label', $record->label)" required />
        </x-field>

        <x-field label="Which Menu" for="menu" :error="$errors->first('menu')">
            <x-select id="menu" name="menu">
                @foreach ($menus as $value => $menuLabel)
                    <option value="{{ $value }}" @selected(old('menu', $record->menu ?: 'primary') === $value)>{{ $menuLabel }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Link To A Page" for="page_id" hint="Takes priority over the manual URL below." :error="$errors->first('page_id')">
            <x-select id="page_id" name="page_id">
                <option value="">Use The URL Below</option>
                @foreach ($pages as $page)
                    <option value="{{ $page->id }}" @selected(old('page_id', $record->page_id) == $page->id)>{{ $page->title }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Or A URL" for="url" :error="$errors->first('url')">
            <x-input id="url" name="url" :value="old('url', $record->url)" placeholder="https://example.gov/pay" />
        </x-field>

        <x-field label="Parent Item" for="parent_id" hint="Nests this item into a dropdown." :error="$errors->first('parent_id')">
            <x-select id="parent_id" name="parent_id">
                <option value="">Top Level</option>
                @foreach ($parents as $parent)
                    @if (! $record->exists || $parent->id !== $record->id)
                        <option value="{{ $parent->id }}" @selected(old('parent_id', $record->parent_id) == $parent->id)>{{ $parent->label }} ({{ \Illuminate\Support\Str::headline($parent->menu) }})</option>
                    @endif
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Icon" for="icon" :error="$errors->first('icon')">
            <x-select id="icon" name="icon">
                <option value="">No Icon</option>
                @foreach ($icons as $icon)
                    <option value="{{ $icon }}" @selected(old('icon', $record->icon) === $icon)>{{ \Illuminate\Support\Str::headline($icon) }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Description" for="description" class="sm:col-span-2" hint="Subtitle shown on quick-link tiles and dropdown items." :error="$errors->first('description')">
            <x-input id="description" name="description" :value="old('description', $record->description)" />
        </x-field>

        <x-field label="Sort Order" for="sort_order" :error="$errors->first('sort_order')">
            <x-input id="sort_order" name="sort_order" type="number" min="0" :value="old('sort_order', $record->sort_order ?? 0)" />
        </x-field>

        <div class="space-y-4">
            <x-toggle name="new_tab" :checked="old('new_tab', $record->new_tab)" label="Open In A New Tab" />
            <x-toggle name="is_published" :checked="old('is_published', $record->is_published ?? true)" label="Show This Item" />
        </div>
    </div>
</x-card>
