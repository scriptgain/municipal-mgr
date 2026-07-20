@props(['id', 'label' => 'Row'])
<button type="button" role="switch"
        :aria-checked="selected.includes({{ $id }}).toString()"
        aria-label="Select {{ $label }}"
        @click="selected.includes({{ $id }}) ? selected = selected.filter(i => i !== {{ $id }}) : selected.push({{ $id }})"
        :class="selected.includes({{ $id }}) ? 'bg-brand-600' : 'bg-slate-300'"
        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors">
    <span :class="selected.includes({{ $id }}) ? 'translate-x-4.5' : 'translate-x-0.5'"
          class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"></span>
</button>
