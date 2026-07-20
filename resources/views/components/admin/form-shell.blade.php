@props(['title', 'subtitle' => null, 'icon' => 'edit', 'action', 'method' => 'POST', 'index', 'record' => null, 'multipart' => false, 'deleteAction' => null])
{{-- Wrapper every admin create/edit screen uses: page header, form element,
     sticky save bar, and (on edit) a delete action behind a modal confirm. --}}
<x-layouts.app :title="$title">
    <x-page-header :title="$title" :subtitle="$subtitle" :icon="$icon">
        <x-slot:actions>
            <x-button variant="secondary" icon="chevron-left" :href="$index">Back To List</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ $action }}" class="space-y-6" @if ($multipart) enctype="multipart/form-data" @endif>
        @csrf
        @if ($method !== 'POST')@method($method)@endif

        {{ $slot }}

        <div class="section-divider pt-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                @if ($record && $record->exists && $deleteAction)
                    <x-delete-button :name="'delete-record-' . $record->getKey()"
                                     :action="$deleteAction"
                                     title="Delete This Record?"
                                     message="This permanently removes the record. Any public page linking to it will show a not-found page." />
                @endif
            </div>
            <div class="flex items-center gap-2">
                <x-button variant="secondary" :href="$index">Cancel</x-button>
                <x-button type="submit" icon="check">Save Changes</x-button>
            </div>
        </div>
    </form>
</x-layouts.app>
