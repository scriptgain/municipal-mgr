@props(['disclaimer', 'takedownContact' => null, 'retentionDays' => null, 'compact' => false])
{{-- The standing notice. Rendered at the TOP of every public arrest-records
     view, not buried in a footer: someone who reads only the first thing on
     the page must still read the part that says an arrest is not a conviction. --}}
<aside role="note" aria-label="Important Notice About These Records"
       class="rounded-2xl bg-amber-50 p-5 sm:p-6 ring-1 ring-inset ring-amber-200">
    <div class="flex items-start gap-4">
        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-amber-600 ring-1 ring-amber-200">
            <x-icon name="scale" class="w-5 h-5" aria-hidden="true" />
        </span>
        <div class="min-w-0">
            <h2 class="font-display text-lg font-semibold text-amber-950">An Arrest Is Not A Conviction</h2>
            <p class="mt-2 text-sm leading-relaxed text-amber-900">{{ $disclaimer }}</p>

            @unless ($compact)
                <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                    @if ($retentionDays)
                        <div class="rounded-xl bg-white/70 px-4 py-3 ring-1 ring-inset ring-amber-200">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-amber-700">How Long Records Stay Posted</dt>
                            <dd class="mt-1 text-sm text-amber-950">{{ $retentionDays }} days from the date of booking, after which the entry is removed from this page.</dd>
                        </div>
                    @endif
                    @if ($takedownContact)
                        <div class="rounded-xl bg-white/70 px-4 py-3 ring-1 ring-inset ring-amber-200">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-amber-700">Corrections And Removal Requests</dt>
                            <dd class="mt-1 text-sm text-amber-950">{{ $takedownContact }}</dd>
                        </div>
                    @endif
                </dl>
            @endunless
        </div>
    </div>
</aside>
