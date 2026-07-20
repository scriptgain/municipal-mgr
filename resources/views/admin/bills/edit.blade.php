<x-admin.form-shell :title="'Edit Bill ' . $record->reference" icon="database"
                    subtitle="Changing a bill does not change payments already taken against it."
                    :action="route('bills.update', $record)"
                    method="PUT"
                    :index="route('bills.index')"
                    :record="$record"
                    :deleteAction="route('bills.destroy', $record)">

    @if ($record->amount_paid_cents > 0)
        <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">
            <span class="font-semibold">{{ $record->paidFormatted() }} has already been paid against this bill.</span>
            The total cannot be reduced below that amount.
        </div>
    @endif

    <x-card title="What Is Being Billed">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field label="Bill Type" for="bill_type_id" required :error="$errors->first('bill_type_id')">
                <x-select id="bill_type_id" name="bill_type_id" required>
                    @foreach ($types as $type)
                        <option value="{{ $type->id }}" @selected(old('bill_type_id', $record->bill_type_id) == $type->id)>{{ $type->label }}</option>
                    @endforeach
                </x-select>
            </x-field>

            <x-field label="Amount" for="amount" required
                     hint="In dollars, for example 124.50."
                     :error="$errors->first('amount')">
                <x-input id="amount" name="amount" inputmode="decimal" required
                         :value="old('amount', $amountDecimal)" class="tabular" />
            </x-field>

            <x-field label="Description" for="description" class="sm:col-span-2" :error="$errors->first('description')">
                <x-input id="description" name="description" maxlength="255" :value="old('description', $record->description)" />
            </x-field>

            <x-field label="Account Or Citation Number" for="account_number" :error="$errors->first('account_number')">
                <x-input id="account_number" name="account_number" maxlength="80" :value="old('account_number', $record->account_number)" class="tabular" />
            </x-field>

            <x-field label="Due Date" for="due_date" :error="$errors->first('due_date')">
                <x-input id="due_date" name="due_date" type="date" :value="old('due_date', $record->due_date?->toDateString())" />
            </x-field>
        </div>
    </x-card>

    <x-card title="Who It Is Billed To">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field label="Name" for="payer_name" :error="$errors->first('payer_name')">
                <x-input id="payer_name" name="payer_name" maxlength="150" :value="old('payer_name', $record->payer_name)" />
            </x-field>

            <x-field label="Email Address" for="payer_email" :error="$errors->first('payer_email')">
                <x-input id="payer_email" name="payer_email" type="email" maxlength="150" :value="old('payer_email', $record->payer_email)" />
            </x-field>

            <x-field label="Phone Number" for="payer_phone" :error="$errors->first('payer_phone')">
                <x-input id="payer_phone" name="payer_phone" type="tel" maxlength="40" :value="old('payer_phone', $record->payer_phone)" />
            </x-field>

            <x-field label="Issued On" for="issued_on" :error="$errors->first('issued_on')">
                <x-input id="issued_on" name="issued_on" type="date" :value="old('issued_on', $record->issued_on?->toDateString())" />
            </x-field>
        </div>
    </x-card>

    <x-card title="Online Lookup Security"
            subtitle="A resident paying online enters the bill reference plus ONE of these.">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field label="Last Name On The Account" for="lookup_surname" :error="$errors->first('lookup_surname')">
                <x-input id="lookup_surname" name="lookup_surname" maxlength="80" :value="old('lookup_surname', $record->lookup_surname)" />
            </x-field>

            <x-field label="Billing ZIP Code" for="lookup_postal_code" :error="$errors->first('lookup_postal_code')">
                <x-input id="lookup_postal_code" name="lookup_postal_code" maxlength="20" :value="old('lookup_postal_code', $record->lookup_postal_code)" class="tabular" />
            </x-field>
        </div>
    </x-card>

    <x-card title="Internal Notes" subtitle="Staff only. Never shown to the resident.">
        <x-field label="Notes" for="notes" :error="$errors->first('notes')">
            <textarea id="notes" name="notes" rows="4" maxlength="2000"
                      class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">{{ old('notes', $record->notes) }}</textarea>
        </x-field>
    </x-card>
</x-admin.form-shell>
