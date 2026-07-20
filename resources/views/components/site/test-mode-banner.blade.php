@props(['maxWidth' => config('municipal.max_width', 'max-w-7xl')])
{{-- Test mode must be unmistakable. A resident should never believe they have
     paid a real bill, and a clerk should never believe they have taken real
     money. Deliberately loud, deliberately not dismissible. --}}
<div role="status" class="border-b border-amber-600 bg-amber-500 text-amber-950">
    <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-start gap-3">
        <x-icon name="warning" class="w-5 h-5 shrink-0 mt-0.5" aria-hidden="true" />
        <div class="min-w-0 flex-1">
            <p class="font-semibold">Test Mode: No Real Payment Will Be Taken</p>
            <p class="mt-0.5 text-sm opacity-90">
                This payment page is running against a test card processor while the town finishes setting it up.
                Do not enter a real card number. Nothing entered here will charge you, and no bill will be settled.
            </p>
        </div>
    </div>
</div>
