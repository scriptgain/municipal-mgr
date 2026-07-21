<x-card title="Release Note">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Version" for="version" required hint="For example, 0.4.0." :error="$errors->first('version')">
            <x-input id="version" name="version" :value="old('version', $record->version)" placeholder="0.4.0" required />
        </x-field>

        <x-field label="Release Date" for="released_on" required :error="$errors->first('released_on')">
            <x-input id="released_on" name="released_on" type="date"
                     :value="old('released_on', $record->released_on?->format('Y-m-d'))" required />
        </x-field>

        <x-field label="Title" for="title" required class="sm:col-span-2" :error="$errors->first('title')">
            <x-input id="title" name="title" :value="old('title', $record->title)" required />
        </x-field>

        <x-field label="Summary" for="summary" hint="One line shown under the title in the timeline." class="sm:col-span-2" :error="$errors->first('summary')">
            <textarea id="summary" name="summary" rows="2"
                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('summary', $record->summary) }}</textarea>
        </x-field>

        <x-field label="Details" for="body" hint="Markdown is supported. Use lists and short paragraphs for what changed." class="sm:col-span-2" :error="$errors->first('body')">
            <textarea id="body" name="body" rows="12"
                      class="block w-full rounded-lg border-0 py-2 px-3 font-mono text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('body', $record->body) }}</textarea>
        </x-field>

        <div class="sm:col-span-2 border-t border-slate-100 pt-5">
            <x-toggle name="is_published" :checked="old('is_published', $record->exists ? $record->is_published : true)"
                      label="Publish On The What's New Page"
                      description="When off, this release note is saved but hidden from the public." />
        </div>
    </div>
</x-card>
