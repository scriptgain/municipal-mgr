@props(['tabs' => [], 'default' => null])
{{-- Tab strip. `tabs` is [key => label] or [key => ['label' =>, 'count' =>]].
     Panels are supplied by <x-tab-panel name="key">. State lives in Alpine so
     switching a tab never costs a page load. --}}
<div x-data="{ tab: '{{ $default ?? array_key_first($tabs) }}' }" {{ $attributes }}>
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex gap-1 overflow-x-auto no-scrollbar" role="tablist" aria-label="Sections">
            @foreach ($tabs as $key => $tab)
                <button type="button" role="tab"
                        :aria-selected="tab === '{{ $key }}'"
                        :tabindex="tab === '{{ $key }}' ? 0 : -1"
                        @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}'
                            ? 'border-brand-600 text-brand-700'
                            : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800'"
                        class="inline-flex shrink-0 items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition">
                    @if (is_array($tab) && ! empty($tab['icon']))
                        <x-icon :name="$tab['icon']" class="w-4 h-4 shrink-0" />
                    @endif
                    {{ is_array($tab) ? $tab['label'] : $tab }}
                    @if (is_array($tab) && isset($tab['count']))
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 tabular">{{ $tab['count'] }}</span>
                    @endif
                </button>
            @endforeach
        </nav>
    </div>
    <div class="pt-6">{{ $slot }}</div>
</div>
