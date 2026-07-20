@props([
    'title', 'icon' => 'folder', 'subtitle' => null,
    'records', 'search' => null, 'placeholder' => 'Search…',
    'createHref' => null, 'createLabel' => 'Add New',
    'bulkAction' => null, 'label' => 'Record',
    'emptyTitle' => 'Nothing Here Yet', 'emptyMessage' => null,
])
{{-- Standard admin list screen: header, search toolbar, massSelect bulk bar,
     the flush-in-card table (supplied as the slot), empty state, pagination.
     Every content type uses this, so staff learn one interaction, not fifteen. --}}
<x-layouts.app :title="$title">
    <x-page-header :title="$title" :icon="$icon" :subtitle="$subtitle">
        <x-slot:actions>
            @isset($actions){{ $actions }}@endisset
            @if ($createHref)
                <x-button :href="$createHref" icon="plus">{{ $createLabel }}</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card flush>
        <div x-data="{{ bulk_state($records->pluck('id')) }}">
            <x-admin.index-toolbar :search="$search" :placeholder="$placeholder"
                                   :createHref="$createHref" :createLabel="$createLabel" />
            @if ($bulkAction)
                <x-bulk-bar :action="$bulkAction" :label="$label" :modal="'bulk-delete-' . \Illuminate\Support\Str::slug($label)" />
            @endif

            @if ($records->count())
                {{ $slot }}
            @else
                <x-admin.empty :title="$emptyTitle" :icon="$icon" :message="$emptyMessage"
                               :href="$createHref" :label="$createLabel" />
            @endif
        </div>

        @if ($records->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $records->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
