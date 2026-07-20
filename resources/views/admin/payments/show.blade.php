<x-layouts.app :title="'Payment ' . $record->reference">
    <x-page-header :title="'Payment ' . $record->reference" icon="database"
                   :subtitle="$record->amountFormatted() . ': ' . $record->instrumentLabel()">
        <x-slot:actions>
            <x-button variant="secondary" icon="chevron-left" :href="route('payments.index')">Back To Payments</x-button>
            <x-button variant="secondary" icon="eye" :href="$receiptUrl" target="_blank" rel="noopener">View Receipt</x-button>
            @if ($record->isRefundable())
                <span x-data @click="$dispatch('open-modal', 'refund-payment')" class="inline-flex">
                    <x-button variant="danger" icon="restore">Refund</x-button>
                </span>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($record->isTestPayment())
        <div role="status" class="mb-5 flex items-start gap-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">
            <x-icon name="warning" class="mt-0.5 w-4 h-4 shrink-0" aria-hidden="true" />
            <p>
                <span class="font-semibold">This Is A Test Payment.</span>
                No real money changed hands and nothing will arrive in the town's bank account.
            </p>
        </div>
    @endif

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-800 ring-1 ring-brand-200">{{ session('status') }}</div>
    @endif
    @error('amount')
        <div class="mb-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-rose-200">{{ $message }}</div>
    @enderror

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Amount" :value="$record->amountFormatted()" icon="database" />
        <x-stat label="Refunded" :value="$record->refundedFormatted()" icon="restore" />
        <x-stat label="Still Refundable" :value="$refundableLabel" icon="scale" />
        <div class="bg-white rounded-xl ring-1 ring-slate-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-brand-50 text-brand-600 ring-1 ring-brand-200">
                    <x-icon name="check-circle" class="w-5 h-5" aria-hidden="true" />
                </span>
                <p class="text-sm font-medium text-slate-500">Status</p>
            </div>
            <div class="mt-3">
                <x-badge :color="$record->statusColor()" dot>{{ $record->statusLabel() }}</x-badge>
            </div>
        </div>
    </div>

    <div class="section-divider my-6"></div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2" title="Payment Details">
            <dl class="grid gap-5 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Payment Reference</dt>
                    <dd class="mt-0.5 font-medium tabular text-slate-900">{{ $record->reference }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Date</dt>
                    <dd class="mt-0.5 text-slate-900">
                        {{ ($record->paid_at ?? $record->created_at)->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Method</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $record->instrumentLabel() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Taken By</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $record->recorder?->name ?? 'Paid Online By The Resident' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Payer</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $record->payer_name ?: 'Not Recorded' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Email</dt>
                    <dd class="mt-0.5 truncate text-slate-900">{{ $record->payer_email ?: 'Not Recorded' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">What It Was For</dt>
                    <dd class="mt-0.5 text-slate-900">
                        @if ($record->bill)
                            <a href="{{ route('bills.show', $record->bill) }}" class="hover:text-brand-700">
                                {{ $record->bill->type?->label }}: {{ $record->bill->reference }}
                            </a>
                        @else
                            {{ $record->type?->label ?? 'Payment' }}
                            @if ($record->notes)
                                <span class="block text-slate-600">{{ $record->notes }}</span>
                            @endif
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Mode</dt>
                    <dd class="mt-0.5 text-slate-900">
                        @if ($record->method !== 'card')
                            Not Applicable
                        @elseif ($record->livemode)
                            <span class="font-medium text-emerald-700">Live</span>
                        @else
                            <span class="font-medium text-amber-700">Test</span>
                        @endif
                    </dd>
                </div>
            </dl>

            @if ($record->failure_reason)
                <div class="section-divider mt-5 pt-5">
                    <h3 class="text-xs font-medium uppercase tracking-wide text-slate-400">Why It Failed</h3>
                    <p class="mt-1.5 text-sm text-rose-700">{{ $record->failure_reason }}</p>
                </div>
            @endif

            @if ($record->method === 'card')
                <div class="section-divider mt-5 pt-5">
                    <h3 class="text-xs font-medium uppercase tracking-wide text-slate-400">Processor References</h3>
                    <dl class="mt-2 space-y-2 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Payment Intent</dt>
                            <dd class="text-right font-mono text-xs text-slate-700">{{ $record->stripe_payment_intent_id ?: ': ' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Charge</dt>
                            <dd class="text-right font-mono text-xs text-slate-700">{{ $record->stripe_charge_id ?: ': ' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Payout</dt>
                            <dd class="text-right font-mono text-xs text-slate-700">
                                {{ $record->stripe_payout_id ?: 'Not Yet Paid Out' }}
                            </dd>
                        </div>
                        @if ($record->payout_arrival_at)
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-slate-500">Payout Arrived</dt>
                                <dd class="text-right text-slate-700">{{ $record->payout_arrival_at->format(config('municipal.date_format')) }}</dd>
                            </div>
                        @endif
                    </dl>
                    <p class="mt-3 text-xs text-slate-500">
                        No card number is stored anywhere in this system. Only the brand and last four digits are
                        kept, so a resident can recognise their own payment.
                    </p>
                </div>
            @endif
        </x-card>

        <div class="space-y-6">
            <x-card title="Resident Record">
                @if ($record->constituent)
                    <div class="flex items-center gap-3">
                        <x-avatar size="sm" :initials="$record->constituent->initials()" :name="$record->constituent->name" />
                        <div class="min-w-0">
                            <a href="{{ route('constituents.show', $record->constituent) }}"
                               class="block truncate font-medium text-slate-900 hover:text-brand-700">{{ $record->constituent->name }}</a>
                            <p class="truncate text-xs text-slate-500">{{ $record->constituent->email ?: 'No Email On File' }}</p>
                        </div>
                    </div>
                    <div class="section-divider mt-5 pt-5">
                        <x-button variant="secondary" size="sm" icon="users"
                                  :href="route('constituents.show', $record->constituent)" class="w-full">
                            Open Resident Record
                        </x-button>
                    </div>
                @else
                    <p class="text-sm text-slate-500">
                        This payment is not linked to a resident record. That happens when no email or phone number
                        was given at the time of payment.
                    </p>
                @endif
            </x-card>

            @if ($record->isRefundable())
                <x-card title="Refund This Payment"
                        subtitle="Sends money back to the card it came from. Refunds usually reach the resident within five to ten working days.">
                    <p class="text-sm text-slate-600">
                        Up to <span class="font-semibold text-slate-900">{{ $refundableLabel }}</span> can still be refunded.
                    </p>
                    <div class="mt-4">
                        <span x-data @click="$dispatch('open-modal', 'refund-payment')" class="inline-flex">
                            <x-button variant="danger" icon="restore">Refund This Payment</x-button>
                        </span>
                    </div>
                </x-card>
            @endif
        </div>
    </div>

    {{-- Refund. Modal confirm, never a native dialog. --}}
    @if ($record->isRefundable())
        <x-modal name="refund-payment" title="Refund This Payment?" icon="warning" tone="danger" maxWidth="max-w-lg"
                 subtitle="This sends public money back out. It is recorded in the audit log against your name.">
            <form method="POST" action="{{ route('payments.refund', $record) }}" class="space-y-4" id="refund-form">
                @csrf

                <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-700 ring-1 ring-slate-200">
                    Payment {{ $record->reference }} for <span class="font-semibold">{{ $record->amountFormatted() }}</span>.
                    Up to <span class="font-semibold">{{ $refundableLabel }}</span> can be refunded.
                </div>

                <x-field label="Refund Amount" for="refund_amount"
                         hint="Leave blank to refund the full remaining amount.">
                    <x-input id="refund_amount" name="amount" inputmode="decimal"
                             :value="old('amount')" :placeholder="$refundableDecimal" class="tabular" />
                </x-field>

                <x-field label="Reason" for="refund_reason" hint="Reported to the card network. Optional.">
                    <x-select id="refund_reason" name="reason">
                        <option value="">Not Specified</option>
                        <option value="requested_by_customer">Requested By The Resident</option>
                        <option value="duplicate">Duplicate Payment</option>
                        <option value="fraudulent">Fraudulent</option>
                    </x-select>
                </x-field>

                <x-field label="Internal Note" for="refund_note" hint="Recorded in the audit log alongside the refund.">
                    <x-input id="refund_note" name="note" maxlength="500" placeholder="Paid twice in error, refunded per clerk" />
                </x-field>
            </form>

            <x-slot:footer>
                <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'refund-payment')">Cancel</x-button>
                <x-button variant="danger" size="sm" icon="restore" type="submit" form="refund-form">Issue Refund</x-button>
            </x-slot:footer>
        </x-modal>
    @endif
</x-layouts.app>
