<x-card title="Form Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Form Name" for="name" required :error="$errors->first('name')">
            <x-input id="name" name="name" :value="old('name', $record->name)" required />
        </x-field>

        <x-field label="Department" for="department_id" :error="$errors->first('department_id')">
            <x-select id="department_id" name="department_id">
                <option value="">Not Department Specific</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id', $record->department_id) == $department->id)>{{ $department->name }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Introduction" for="description" class="sm:col-span-2" hint="Shown above the form on the public page." :error="$errors->first('description')">
            <textarea id="description" name="description" rows="3"
                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('description', $record->description) }}</textarea>
        </x-field>

        <x-field label="Notify This Email" for="notify_email" hint="Each submission is emailed here." :error="$errors->first('notify_email')">
            <x-input id="notify_email" name="notify_email" type="email" :value="old('notify_email', $record->notify_email)" />
        </x-field>

        <x-field label="Thank You Message" for="success_message" :error="$errors->first('success_message')">
            <x-input id="success_message" name="success_message" :value="old('success_message', $record->success_message)" />
        </x-field>

        <div class="min-w-0 sm:col-span-2 space-y-4">
            <x-toggle name="store_submissions" :checked="old('store_submissions', $record->store_submissions ?? true)"
                      label="Store Submissions In The Panel"
                      description="Turn off for forms that only need to be emailed." />
            <x-toggle name="is_published" :checked="old('is_published', $record->is_published ?? true)"
                      label="Form Is Live On The Public Site" />
        </div>
    </div>
</x-card>

<x-card title="Fields" subtitle="Add the questions this form asks. Field keys are generated from the labels.">
    <div x-data="{
            fields: @js(old('fields', $record->builderRows())),
            add() { this.fields.push({ label: '', type: 'text', required: false, help: '', options: '' }); },
            remove(i) { this.fields.splice(i, 1); },
            move(i, d) {
                const j = i + d;
                if (j < 0 || j >= this.fields.length) return;
                const t = this.fields[i]; this.fields[i] = this.fields[j]; this.fields[j] = t;
            },
            needsOptions(type) { return ['select', 'radio'].includes(type); }
         }">
        <template x-if="fields.length === 0">
            <p class="rounded-lg bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">This Form Has No Fields Yet.</p>
        </template>

        <div class="space-y-4">
            <template x-for="(field, index) in fields" :key="index">
                <div class="rounded-xl ring-1 ring-slate-200 bg-slate-50/60 p-4">
                    <div class="flex items-center justify-between gap-3 pb-3">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Field <span x-text="index + 1"></span>
                        </span>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="move(index, -1)" data-tip="Move Up" aria-label="Move Field Up"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-white transition">
                                <x-icon name="chevron-up" class="w-4 h-4" />
                            </button>
                            <button type="button" @click="move(index, 1)" data-tip="Move Down" aria-label="Move Field Down"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-white transition">
                                <x-icon name="chevron-down" class="w-4 h-4" />
                            </button>
                            <button type="button" @click="remove(index)" data-tip="Remove Field" aria-label="Remove Field"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-rose-500 hover:bg-rose-50 transition">
                                <x-icon name="trash" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700" :for="`field-label-${index}`">Question Label</label>
                            <input type="text" :id="`field-label-${index}`" :name="`fields[${index}][label]`" x-model="field.label"
                                   class="mt-1.5 block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" :for="`field-type-${index}`">Answer Type</label>
                            <select :id="`field-type-${index}`" :name="`fields[${index}][type]`" x-model="field.type"
                                    class="mt-1.5 block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                                @foreach ($fieldTypes as $value => $typeLabel)
                                    <option value="{{ $value }}">{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="min-w-0 sm:col-span-2" x-show="needsOptions(field.type)" x-cloak>
                            <label class="block text-sm font-medium text-slate-700" :for="`field-options-${index}`">Choices</label>
                            <textarea :id="`field-options-${index}`" :name="`fields[${index}][options]`" x-model="field.options" rows="3"
                                      class="mt-1.5 block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600"></textarea>
                            <p class="mt-1 text-xs text-slate-500">One Choice Per Line.</p>
                        </div>
                        <div class="min-w-0 sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700" :for="`field-help-${index}`">Help Text</label>
                            <input type="text" :id="`field-help-${index}`" :name="`fields[${index}][help]`" x-model="field.help"
                                   class="mt-1.5 block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                        </div>
                        <div class="min-w-0 sm:col-span-2">
                            <label class="flex items-start gap-3 cursor-pointer select-none">
                                <input type="hidden" :name="`fields[${index}][required]`" :value="field.required ? 1 : 0">
                                <button type="button" role="switch" :aria-checked="field.required.toString()"
                                        @click="field.required = ! field.required"
                                        :class="field.required ? 'bg-brand-600' : 'bg-slate-300'"
                                        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors">
                                    <span :class="field.required ? 'translate-x-6' : 'translate-x-1'"
                                          class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"></span>
                                </button>
                                <span class="text-sm font-medium text-slate-900">Answer Is Required</span>
                            </label>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="section-divider mt-5 pt-5">
            <x-button type="button" variant="secondary" icon="plus" x-on:click="add()">Add A Field</x-button>
        </div>
    </div>
</x-card>
