{{-- Shared create/edit fields for a resident record. Markup only. --}}
<x-card title="Who They Are" subtitle="Name is required. Email or phone is what lets the system recognise them next time.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Full Name" for="name" required :error="$errors->first('name')">
            <x-input id="name" name="name" required maxlength="150"
                     :value="old('name', $record->name)" placeholder="Marisol Vega" />
        </x-field>

        <x-field label="Email Address" for="email" :error="$errors->first('email')"
                 hint="Used to match this person to anything they file online.">
            <x-input type="email" id="email" name="email" maxlength="150"
                     :value="old('email', $record->email)" placeholder="resident@example.com" />
        </x-field>

        <x-field label="Phone Number" for="phone" :error="$errors->first('phone')"
                 hint="Any format. Stored digits-only for matching.">
            <x-input id="phone" name="phone" maxlength="40"
                     :value="old('phone', $record->phone)" placeholder="(928) 555-0142" />
        </x-field>

        <x-field label="Tags" for="tags" :error="$errors->first('tags')"
                 hint="Comma separated. For example: Business Owner, Council District 2.">
            <x-input id="tags" name="tags" maxlength="500"
                     :value="old('tags', implode(', ', $record->tagList()))" placeholder="Business Owner, Snowbird" />
        </x-field>
    </div>
</x-card>

<x-card title="Mailing Address">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field class="sm:col-span-2" label="Street Address" for="address_line1" :error="$errors->first('address_line1')">
            <x-input id="address_line1" name="address_line1" maxlength="200"
                     :value="old('address_line1', $record->address_line1)" placeholder="118 South Main Street" />
        </x-field>

        <x-field class="sm:col-span-2" label="Address Line Two" for="address_line2" :error="$errors->first('address_line2')">
            <x-input id="address_line2" name="address_line2" maxlength="200"
                     :value="old('address_line2', $record->address_line2)" placeholder="Apartment, Suite, Or Unit" />
        </x-field>

        <x-field label="City" for="city" :error="$errors->first('city')">
            <x-input id="city" name="city" maxlength="120"
                     :value="old('city', $record->city)" placeholder="Cottonwood Springs" />
        </x-field>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-field label="State" for="state" :error="$errors->first('state')">
                <x-input id="state" name="state" maxlength="60" :value="old('state', $record->state)" placeholder="AZ" />
            </x-field>

            <x-field label="ZIP Code" for="postal_code" :error="$errors->first('postal_code')">
                <x-input id="postal_code" name="postal_code" maxlength="20"
                         :value="old('postal_code', $record->postal_code)" placeholder="86326" />
            </x-field>
        </div>
    </div>
</x-card>

<x-card title="Notes And Handling" subtitle="Internal only. Nothing on this page is ever shown on the public site.">
    <div class="space-y-5">
        <x-field label="Staff Notes" for="notes" :error="$errors->first('notes')"
                 hint="Context the next person to pick up the phone will want.">
            <textarea id="notes" name="notes" rows="5" maxlength="10000"
                      class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">{{ old('notes', $record->notes) }}</textarea>
        </x-field>

        <div class="section-divider pt-5">
            <x-toggle name="do_not_contact" :checked="(bool) old('do_not_contact', $record->do_not_contact)"
                      label="Do Not Contact"
                      description="Flags the record so staff know this resident has asked not to be contacted." />
        </div>
    </div>
</x-card>
