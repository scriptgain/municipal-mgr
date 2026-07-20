@props(['name', 'action', 'reference' => null, 'trigger' => 'icon'])
{{-- Expungement, deliberately NOT the delete button.

     Delete means "this was entered wrong". Expunge means "a court ordered this
     destroyed", so the modal asks who ordered it before it will proceed, and
     the confirmation text spells out that this is permanent and that a
     compliance entry is written. Tooltip is a fixed-position node teleported
     to <body>, never a CSS pseudo-element. --}}
<span class="inline-flex" x-data="{ tip: false, tx: 0, ty: 0 }"
      @mouseenter="const r = $el.getBoundingClientRect(); tx = r.left + r.width / 2; ty = r.top - 8; tip = true"
      @mouseleave="tip = false">
    @if ($trigger === 'button')
        <x-button type="button" variant="secondary" icon="scale"
                  x-on:click="$dispatch('open-modal', '{{ $name }}')">Expunge This Record</x-button>
    @else
        <button type="button" @click="$dispatch('open-modal', '{{ $name }}')"
                aria-label="Expunge Under A Court Order"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-amber-600 ring-1 ring-inset ring-amber-200 transition hover:bg-amber-50 hover:ring-amber-300">
            <x-icon name="scale" class="w-4 h-4" aria-hidden="true" />
        </button>
    @endif

    <template x-teleport="body">
        <div x-show="tip" x-cloak :style="`left:${tx}px;top:${ty}px`"
             class="fixed -translate-x-1/2 -translate-y-full pointer-events-none z-[100] whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs font-medium text-white shadow-lg">
            Expunge Under A Court Order
        </div>
    </template>
</span>

<x-modal :name="$name" title="Carry Out An Expungement Order?" tone="warn" icon="scale" maxWidth="max-w-lg"
         :subtitle="$reference ? 'Record ' . $reference : null">
    <form method="POST" action="{{ $action }}" id="expunge-{{ $name }}" class="space-y-4">
        @csrf
        @method('DELETE')

        <p>
            This permanently destroys the record, its charges, and its booking photograph.
            It is not an unpublish and it cannot be undone. A compliance entry recording
            that the order was carried out, by whom, and on what date is written to the
            Expungement Log and the audit log. That entry does not keep the subject's name.
        </p>

        <x-field label="Ordered By" :for="'ordered-by-' . $name" required
                 hint="The court or authority that issued the sealing or expungement order.">
            <x-input :id="'ordered-by-' . $name" name="ordered_by" required
                     placeholder="Yavapai County Superior Court" />
        </x-field>

        <x-field label="Order Reference" :for="'order-ref-' . $name"
                 hint="Docket or order number, if the order carries one.">
            <x-input :id="'order-ref-' . $name" name="order_reference" placeholder="CR-2026-000000" />
        </x-field>

        <x-field label="Notes" for="order-reason-{{ $name }}"
                 hint="Optional. Kept in the compliance log.">
            <textarea id="order-reason-{{ $name }}" name="reason" rows="3"
                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600"></textarea>
        </x-field>
    </form>

    <x-slot:footer>
        <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', '{{ $name }}')">Cancel</x-button>
        <x-button variant="danger" size="sm" icon="scale" type="submit" form="expunge-{{ $name }}">Expunge Permanently</x-button>
    </x-slot:footer>
</x-modal>
