<x-card title="Page Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Page Title" for="title" required class="sm:col-span-2" :error="$errors->first('title')">
            <x-input id="title" name="title" :value="old('title', $record->title)" required />
        </x-field>

        <x-field label="Summary" for="summary" hint="One or two sentences. Shown in search results and card listings." class="sm:col-span-2" :error="$errors->first('summary')">
            <x-input id="summary" name="summary" :value="old('summary', $record->summary)" />
        </x-field>

        <x-field label="Parent Page" for="parent_id" hint="Nests this page for breadcrumbs." :error="$errors->first('parent_id')">
            <x-select id="parent_id" name="parent_id">
                <option value="">No Parent (Top Level)</option>
                @foreach ($parents as $parent)
                    @if (! $record->exists || $parent->id !== $record->id)
                        <option value="{{ $parent->id }}" @selected(old('parent_id', $record->parent_id) == $parent->id)>{{ $parent->title }}</option>
                    @endif
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

        <x-field label="Layout Template" for="template" :error="$errors->first('template')">
            <x-select id="template" name="template">
                @foreach (['standard' => 'Standard (With Sidebar)', 'wide' => 'Wide (Full Width)', 'landing' => 'Landing Page'] as $value => $optionLabel)
                    <option value="{{ $value }}" @selected(old('template', $record->template ?: 'standard') === $value)>{{ $optionLabel }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Status" for="status" :error="$errors->first('status')">
            <x-select id="status" name="status">
                <option value="draft" @selected(old('status', $record->status ?: 'draft') === 'draft')>Draft (Staff Only)</option>
                <option value="published" @selected(old('status', $record->status) === 'published')>Published (Public)</option>
            </x-select>
        </x-field>

        <x-field label="Sort Order" for="sort_order" hint="Lower numbers appear first." :error="$errors->first('sort_order')">
            <x-input id="sort_order" name="sort_order" type="number" min="0" :value="old('sort_order', $record->sort_order ?? 0)" />
        </x-field>

        <x-field label="Search Engine Description" for="meta_description" class="sm:col-span-2" :error="$errors->first('meta_description')">
            <x-input id="meta_description" name="meta_description" :value="old('meta_description', $record->meta_description)" />
        </x-field>

        <div class="sm:col-span-2">
            <x-toggle name="show_in_nav" :checked="old('show_in_nav', $record->show_in_nav)"
                      label="Show In Navigation"
                      description="Adds this page to the primary menu automatically." />
        </div>
    </div>
</x-card>

{{-- Page builder: repeatable section blocks. Alpine handles add/remove/reorder
     entirely client side; the server stores whatever rows survive. --}}
<x-card title="Page Sections" subtitle="Build the page from blocks. Drag order is set by the arrows.">
    <div x-data="{
            blocks: @js(old('sections', $record->sections ?? [])),
            add(type) { this.blocks.push({ type: type, heading: '', body: '' }); },
            remove(i) { this.blocks.splice(i, 1); },
            move(i, delta) {
                const j = i + delta;
                if (j < 0 || j >= this.blocks.length) return;
                const tmp = this.blocks[i]; this.blocks[i] = this.blocks[j]; this.blocks[j] = tmp;
            }
         }">
        <template x-if="blocks.length === 0">
            <p class="rounded-lg bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                No Sections Yet. Add One Below To Start Building This Page.
            </p>
        </template>

        <div class="space-y-4">
            <template x-for="(block, index) in blocks" :key="index">
                <div class="rounded-xl ring-1 ring-slate-200 bg-slate-50/60 p-4">
                    <div class="flex items-center justify-between gap-3 pb-3">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold text-brand-700 ring-1 ring-brand-100">
                            <span x-text="({{ Js::from($sectionTypes) }})[block.type] || block.type"></span>
                        </span>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="move(index, -1)" data-tip="Move Up" aria-label="Move Section Up"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-white hover:text-brand-700 transition">
                                <x-icon name="chevron-up" class="w-4 h-4" />
                            </button>
                            <button type="button" @click="move(index, 1)" data-tip="Move Down" aria-label="Move Section Down"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-white hover:text-brand-700 transition">
                                <x-icon name="chevron-down" class="w-4 h-4" />
                            </button>
                            <button type="button" @click="remove(index)" data-tip="Remove Section" aria-label="Remove Section"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-rose-500 hover:bg-rose-50 transition">
                                <x-icon name="trash" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <input type="hidden" :name="`sections[${index}][type]`" :value="block.type">

                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700" :for="`section-heading-${index}`">Section Heading</label>
                            <input type="text" :id="`section-heading-${index}`" :name="`sections[${index}][heading]`" x-model="block.heading"
                                   class="mt-1.5 block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" :for="`section-body-${index}`">Content</label>
                            <textarea :id="`section-body-${index}`" :name="`sections[${index}][body]`" x-model="block.body" rows="5"
                                      class="mt-1.5 block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600"></textarea>
                            <p class="mt-1 text-xs text-slate-500">Basic HTML is allowed for links, lists, and tables.</p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="section-divider mt-5 pt-5">
            <p class="text-sm font-medium text-slate-700">Add A Section</p>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($sectionTypes as $type => $typeLabel)
                    <button type="button" @click="add('{{ $type }}')"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-brand-50 hover:text-brand-800 hover:ring-brand-200 transition">
                        <x-icon name="plus" class="w-4 h-4 text-slate-400" /> {{ $typeLabel }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</x-card>
