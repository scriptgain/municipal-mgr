<x-card title="Alert Content" subtitle="Emergency alerts cannot be dismissed by the visitor.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Alert Title" for="title" required class="sm:col-span-2" :error="$errors->first('title')">
            <x-input id="title" name="title" :value="old('title', $record->title)" required placeholder="Boil Water Advisory In Effect" />
        </x-field>

        <x-field label="Message" for="message" class="sm:col-span-2" :error="$errors->first('message')">
            <textarea id="message" name="message" rows="3"
                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('message', $record->message) }}</textarea>
        </x-field>

        <x-field label="Severity" for="level" :error="$errors->first('level')">
            <x-select id="level" name="level">
                @foreach ($levels as $value => $levelLabel)
                    <option value="{{ $value }}" @selected(old('level', $record->level ?: 'info') === $value)>{{ $levelLabel }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Link Label" for="link_label" :error="$errors->first('link_label')">
            <x-input id="link_label" name="link_label" :value="old('link_label', $record->link_label)" placeholder="Read The Full Advisory" />
        </x-field>

        <x-field label="Link URL" for="link_url" class="sm:col-span-2" :error="$errors->first('link_url')">
            <x-input id="link_url" name="link_url" type="url" :value="old('link_url', $record->link_url)" />
        </x-field>

        <x-field label="Show From" for="starts_at" hint="Leave blank to start immediately." :error="$errors->first('starts_at')">
            <x-input id="starts_at" name="starts_at" type="datetime-local" :value="old('starts_at', $record->starts_at?->format('Y-m-d\TH:i'))" />
        </x-field>

        <x-field label="Hide After" for="ends_at" hint="The banner retires itself automatically." :error="$errors->first('ends_at')">
            <x-input id="ends_at" name="ends_at" type="datetime-local" :value="old('ends_at', $record->ends_at?->format('Y-m-d\TH:i'))" />
        </x-field>

        <div class="sm:col-span-2 space-y-4">
            <x-toggle name="is_active" :checked="old('is_active', $record->is_active ?? true)"
                      label="Alert Is Active"
                      description="Turn this off to stage an alert without showing it." />
            <x-toggle name="is_dismissible" :checked="old('is_dismissible', $record->is_dismissible ?? true)"
                      label="Visitors May Dismiss It"
                      description="Ignored for emergency alerts, which always stay visible." />
        </div>
    </div>
</x-card>
