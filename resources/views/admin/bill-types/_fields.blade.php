{{-- Shared create/edit fields for a bill type. --}}
<x-card title="What Residents See">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Label" for="label" required
                 hint="What a resident sees on the payment page. For example: Water And Sewer Bill."
                 :error="$errors->first('label')">
            <x-input id="label" name="label" required maxlength="120" :value="old('label', $record->label)" />
        </x-field>

        <x-field label="Icon" for="icon" :error="$errors->first('icon')">
            <x-select id="icon" name="icon">
                @foreach ($iconOptions as $icon)
                    <option value="{{ $icon }}" @selected(old('icon', $record->icon ?: 'file-text') === $icon)>{{ $icon }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Description" for="description" class="sm:col-span-2"
                 hint="One line explaining what this covers. Shown under the label."
                 :error="$errors->first('description')">
            <textarea id="description" name="description" rows="2" maxlength="1000"
                      class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">{{ old('description', $record->description) }}</textarea>
        </x-field>
    </div>
</x-card>

<x-card title="How Residents Pay It">
    <div class="space-y-6">
        <x-toggle name="requires_lookup"
                  :checked="old('requires_lookup', $record->exists ? $record->requires_lookup : true)"
                  label="Requires A Bill Reference"
                  description="The resident must look their bill up by reference number plus a second factor. Correct for utility bills and citations, where you issued the bill." />

        <x-toggle name="allows_open_payment"
                  :checked="old('allows_open_payment', $record->allows_open_payment)"
                  label="Can Be Paid Without A Bill Reference"
                  description="The resident types the amount themselves. Correct for permit fees and facility rentals, where no bill was ever issued. Set sensible limits below." />

        <x-toggle name="is_active"
                  :checked="old('is_active', $record->exists ? $record->is_active : true)"
                  label="Visible On The Public Site"
                  description="Switch this off to retire a type without deleting the bills raised under it." />
    </div>
</x-card>

<x-card title="Limits And Routing"
        subtitle="Limits apply only when a resident types the amount themselves. A bill-backed payment always charges the bill's balance.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Minimum Amount" for="min_amount"
                 hint="Leave blank to use the module default."
                 :error="$errors->first('min_amount')">
            <x-input id="min_amount" name="min_amount" inputmode="decimal"
                     :value="old('min_amount', $record->minDecimal())" placeholder="1.00" class="tabular" />
        </x-field>

        <x-field label="Maximum Amount" for="max_amount"
                 hint="A ceiling protects against a mistyped amount, for example 50000 instead of 500.00."
                 :error="$errors->first('max_amount')">
            <x-input id="max_amount" name="max_amount" inputmode="decimal"
                     :value="old('max_amount', $record->maxDecimal())" placeholder="5000.00" class="tabular" />
        </x-field>

        <x-field label="Department" for="department_id"
                 hint="For reporting. Does not change where the money goes."
                 :error="$errors->first('department_id')">
            <x-select id="department_id" name="department_id">
                <option value="">Not Department Specific</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id', $record->department_id) == $department->id)>{{ $department->name }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Sort Order" for="sort_order"
                 hint="Lower numbers appear first on the public page."
                 :error="$errors->first('sort_order')">
            <x-input id="sort_order" name="sort_order" type="number" min="0" max="9999"
                     :value="old('sort_order', $record->sort_order ?? 0)" class="tabular" />
        </x-field>

        <x-field label="Key" for="key" class="sm:col-span-2"
                 hint="Used in the public payment URL. Generated from the label if left blank. Changing it breaks any link already printed on a bill."
                 :error="$errors->first('key')">
            <x-input id="key" name="key" maxlength="64" :value="old('key', $record->key)" placeholder="utility" />
        </x-field>
    </div>
</x-card>
