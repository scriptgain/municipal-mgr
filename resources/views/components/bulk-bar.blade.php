@props(['action', 'label' => 'Record', 'modal' => 'bulk-delete'])
{{-- massSelect bulk delete. Selection state lives in the parent x-data
     ("selected"); confirmation goes through a modal, never a native confirm(). --}}
<form method="POST" action="{{ $action }}" x-ref="bulkForm" class="hidden">
    @csrf @method('DELETE')
</form>

<div x-show="selected.length" x-cloak
     class="flex flex-wrap items-center justify-between gap-3 border-b border-brand-200 bg-brand-50 px-4 py-2.5">
    <span class="text-sm font-medium text-brand-800">
        <span x-text="selected.length"></span> Selected
    </span>
    <div class="flex items-center gap-2">
        <x-button type="button" variant="secondary" size="sm" x-on:click="selected = []">Clear Selection</x-button>
        <x-button type="button" variant="danger" size="sm" icon="trash"
                  x-on:click="$dispatch('open-modal', '{{ $modal }}')">Delete Selected</x-button>
    </div>
</div>

<x-modal :name="$modal" title="Delete Selected {{ $label }} Records?" tone="danger" icon="warning" maxWidth="max-w-md">
    This permanently removes <span class="font-semibold" x-text="selected.length"></span> selected record(s).
    Published pages linking to them will show a not-found page. This cannot be undone.
    <x-slot:footer>
        <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', '{{ $modal }}')">Cancel</x-button>
        <x-button variant="danger" size="sm" icon="trash" x-on:click="submitBulk()">Delete Permanently</x-button>
    </x-slot:footer>
</x-modal>
