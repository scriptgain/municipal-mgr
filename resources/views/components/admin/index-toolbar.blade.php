@props(['search' => null, 'placeholder' => 'Search…', 'createHref' => null, 'createLabel' => 'Add New'])
<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3">
    <form method="GET" class="flex items-center gap-2">
        <label class="sr-only" for="q">Search</label>
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 w-4 h-4 -translate-y-1/2 text-slate-400" />
            <input id="q" name="q" type="search" value="{{ $search }}" placeholder="{{ $placeholder }}"
                   class="w-64 rounded-lg border-0 py-2 pl-9 pr-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-600">
        </div>
        <x-button type="submit" variant="secondary" size="sm">Search</x-button>
        @if ($search)
            <x-button variant="ghost" size="sm" :href="url()->current()">Clear</x-button>
        @endif
    </form>
    @if ($createHref)
        <x-button :href="$createHref" icon="plus" size="sm">{{ $createLabel }}</x-button>
    @endif
</div>
