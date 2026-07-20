@props(['ids' => []])
{{-- Header toggle: selects/clears every row on the page. A toggle switch, not
     a bare checkbox (house style). --}}
<button type="button" role="switch"
        :aria-checked="(selected.length === allIds.length && allIds.length > 0).toString()"
        aria-label="Select All Rows"
        @click="selected = (selected.length === allIds.length ? [] : [...allIds])"
        :class="selected.length === allIds.length && allIds.length > 0 ? 'bg-brand-600' : 'bg-slate-300'"
        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors">
    <span :class="selected.length === allIds.length && allIds.length > 0 ? 'translate-x-4.5' : 'translate-x-0.5'"
          class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"></span>
</button>
