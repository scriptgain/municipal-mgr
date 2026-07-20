@props(['supportEmail' => null, 'supportPhone' => null])
{{-- Shown when the module is switched on but the connected Stripe account
     cannot currently take a charge (onboarding unfinished, or restricted).
     Honest about it rather than presenting a card form that would fail. --}}
<div role="alert" class="rounded-2xl bg-amber-50 p-6 ring-1 ring-amber-200">
    <div class="flex items-start gap-3">
        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-amber-600 ring-1 ring-amber-200">
            <x-icon name="warning" class="w-5 h-5" aria-hidden="true" />
        </span>
        <div class="min-w-0">
            <h2 class="text-base font-semibold text-amber-900">Online Payments Are Temporarily Unavailable</h2>
            <p class="mt-1 text-sm leading-relaxed text-amber-800">
                We cannot take card payments on this site at the moment. Your bill is still due, and you can
                pay it at the town offices or by mail in the meantime. We are sorry for the inconvenience.
            </p>
            @if ($supportEmail || $supportPhone)
                <p class="mt-3 text-sm text-amber-800">
                    Questions about a bill:
                    @if ($supportPhone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $supportPhone) }}" class="font-semibold underline underline-offset-2">{{ $supportPhone }}</a>
                    @endif
                    @if ($supportEmail && $supportPhone) or @endif
                    @if ($supportEmail)
                        <a href="mailto:{{ $supportEmail }}" class="font-semibold underline underline-offset-2">{{ $supportEmail }}</a>
                    @endif
                </p>
            @endif
        </div>
    </div>
</div>
