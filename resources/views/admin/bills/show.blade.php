<x-layouts.app :title="'Bill ' . $record->reference">
    <x-page-header :title="'Bill ' . $record->reference" icon="database"
                   :subtitle="$record->type?->label . ($record->description ? ': ' . $record->description : '')">
        <x-slot:actions>
            <x-button variant="secondary" icon="chevron-left" :href="route('bills.index')">Back To Bills</x-button>
            <x-button variant="secondary" icon="edit" :href="route('bills.edit', $record)">Edit Bill</x-button>
            @if ($record->isPayable())
                <span x-data @click="$dispatch('open-modal', 'mark-paid')" class="inline-flex">
                    <x-button icon="check">Record A Payment</x-button>
                </span>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-800 ring-1 ring-brand-200">{{ session('status') }}</div>
    @endif
    @error('amount')
        <div class="mb-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-rose-200">{{ $message }}</div>
    @enderror

    {{-- Money summary --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Bill Total" :value="$record->amountFormatted()" icon="database" />
        <x-stat label="Paid" :value="$record->paidFormatted()" icon="check-circle" />
        <x-stat label="Balance" :value="$record->balanceFormatted()" icon="file-text" />
        <div class="bg-white rounded-xl ring-1 ring-slate-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-brand-50 text-brand-600 ring-1 ring-brand-200">
                    <x-icon name="clock" class="w-5 h-5" aria-hidden="true" />
                </span>
                <p class="text-sm font-medium text-slate-500">Status</p>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-badge :color="$record->statusColor()" dot>
                    {{ $record->isOverdue() ? 'Overdue' : $record->statusLabel() }}
                </x-badge>
                <span class="text-sm text-slate-500">{{ $record->dueLabel() }}</span>
            </div>
        </div>
    </div>

    <div class="section-divider my-6"></div>

    <x-tabs :tabs="[
        'payments' => ['label' => 'Payments', 'icon' => 'database', 'count' => $payments->count()],
        'details' => ['label' => 'Bill Details', 'icon' => 'file-text'],
        'actions' => ['label' => 'Staff Actions', 'icon' => 'bolt'],
    ]">

        {{-- Payments against this bill --}}
        <x-tab-panel name="payments">
            <x-card flush title="Payments Against This Bill"
                    subtitle="Online card payments and anything taken at the counter or by mail.">
                @if ($payments->count())
                    <x-table flush>
                        <caption class="sr-only">Payments Against Bill {{ $record->reference }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Reference</th>
                                <th scope="col">Date</th>
                                <th scope="col">Method</th>
                                <th scope="col" class="text-right">Amount</th>
                                <th scope="col">Status</th>
                                <th scope="col">Taken By</th>
                                <th scope="col" class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $payment)
                                <tr>
                                    <td class="font-medium tabular text-slate-900">
                                        <a href="{{ route('payments.show', $payment) }}" class="hover:text-brand-700">{{ $payment->reference }}</a>
                                        @if ($payment->isTestPayment())
                                            <x-badge color="warn" class="ml-1.5">Test</x-badge>
                                        @endif
                                    </td>
                                    <td class="text-slate-500">{{ ($payment->paid_at ?? $payment->created_at)->format(config('municipal.date_format')) }}</td>
                                    <td class="text-slate-600">{{ $payment->instrumentLabel() }}</td>
                                    <td class="text-right tabular">
                                        {{ $payment->amountFormatted() }}
                                        @if ($payment->refunded_cents > 0)
                                            <span class="block text-xs text-slate-500">- {{ $payment->refundedFormatted() }} refunded</span>
                                        @endif
                                    </td>
                                    <td><x-badge :color="$payment->statusColor()" dot>{{ $payment->statusLabel() }}</x-badge></td>
                                    <td class="text-slate-600">{{ $payment->recorder?->name ?? 'Paid Online' }}</td>
                                    <td class="text-right">
                                        <x-button size="sm" variant="secondary" :href="route('payments.show', $payment)">Open</x-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @else
                    <x-admin.empty icon="database" title="No Payments Yet"
                                   message="Nothing has been paid against this bill." />
                @endif
            </x-card>
        </x-tab-panel>

        {{-- Bill details --}}
        <x-tab-panel name="details">
            <div class="grid gap-6 lg:grid-cols-3">
                <x-card class="lg:col-span-2" title="Bill Details">
                    <dl class="grid gap-5 sm:grid-cols-2 text-sm">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Reference</dt>
                            <dd class="mt-0.5 font-medium tabular text-slate-900">{{ $record->reference }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Bill Type</dt>
                            <dd class="mt-0.5 text-slate-900">{{ $record->type?->label ?? 'Unknown' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Account Number</dt>
                            <dd class="mt-0.5 tabular text-slate-900">{{ $record->account_number ?: 'Not Recorded' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Description</dt>
                            <dd class="mt-0.5 text-slate-900">{{ $record->description ?: 'None' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Issued On</dt>
                            <dd class="mt-0.5 text-slate-900">{{ $record->issued_on?->format(config('municipal.date_format')) ?? 'Not Recorded' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Due Date</dt>
                            <dd class="mt-0.5 text-slate-900">{{ $record->due_date?->format(config('municipal.date_format')) ?? 'No Due Date' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Billed To</dt>
                            <dd class="mt-0.5 text-slate-900">{{ $record->payer_name ?: 'Not Recorded' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Email</dt>
                            <dd class="mt-0.5 truncate text-slate-900">{{ $record->payer_email ?: 'Not Recorded' }}</dd>
                        </div>
                    </dl>

                    @if ($record->notes)
                        <div class="section-divider mt-5 pt-5">
                            <h3 class="text-xs font-medium uppercase tracking-wide text-slate-400">Internal Notes</h3>
                            <p class="mt-1.5 whitespace-pre-line text-sm text-slate-700">{{ $record->notes }}</p>
                        </div>
                    @endif
                </x-card>

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
                            This bill is not linked to a resident record. It will link automatically when it is paid,
                            if the email or phone matches someone already on file.
                        </p>
                    @endif

                    <div class="section-divider mt-5 pt-5">
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-slate-500">Raised By</dt>
                                <dd class="text-right text-slate-900">{{ $record->creator?->name ?? 'Imported' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-slate-500">Created</dt>
                                <dd class="text-right text-slate-900">{{ $record->created_at->format(config('municipal.date_format')) }}</dd>
                            </div>
                        </dl>
                    </div>
                </x-card>
            </div>
        </x-tab-panel>

        {{-- Staff actions --}}
        <x-tab-panel name="actions">
            <div class="grid gap-6 lg:grid-cols-2">
                <x-card title="Record A Payment Taken Offline"
                        subtitle="Cash at the counter, a check in the mail, a money order. This does not touch the card processor.">
                    @if ($record->isPayable())
                        <p class="text-sm text-slate-600">
                            {{ $record->balanceFormatted() }} is currently outstanding.
                        </p>
                        <div class="mt-4">
                            <span x-data @click="$dispatch('open-modal', 'mark-paid')" class="inline-flex">
                                <x-button icon="check">Record A Payment</x-button>
                            </span>
                        </div>
                    @else
                        <p class="text-sm text-slate-500">
                            This bill is {{ strtolower($record->statusLabel()) }} and is not open for payment.
                        </p>
                    @endif
                </x-card>

                <x-card title="Void Or Reinstate"
                        subtitle="Voiding stops a bill being payable and takes it out of the outstanding total. It is never deleted: the record stays for audit.">
                    @if ($record->status === 'void')
                        <p class="text-sm text-slate-600">This bill is voided.</p>
                        <div class="mt-4">
                            <x-confirm-action name="reinstate-bill"
                                              :action="route('bills.reinstate', $record)"
                                              title="Reinstate This Bill?"
                                              message="The bill becomes payable again and returns to the outstanding total."
                                              confirm="Reinstate Bill"
                                              confirmIcon="restore">
                                <x-button variant="secondary" icon="restore">Reinstate Bill</x-button>
                            </x-confirm-action>
                        </div>
                    @else
                        <form method="POST" action="{{ route('bills.void', $record) }}" id="void-bill-form">
                            @csrf
                            <x-field label="Reason" for="reason" hint="Recorded in the audit log and on the bill.">
                                <x-input id="reason" name="reason" maxlength="500" placeholder="Billed in error" />
                            </x-field>
                        </form>
                        <div class="mt-4">
                            <span x-data @click="$dispatch('open-modal', 'void-bill')" class="inline-flex">
                                <x-button variant="danger" icon="x-circle">Void This Bill</x-button>
                            </span>
                        </div>
                    @endif
                </x-card>
            </div>
        </x-tab-panel>
    </x-tabs>

    {{-- Record an offline payment. A modal, so the page never grows a long scroll. --}}
    <x-modal name="mark-paid" title="Record A Payment" icon="check"
             subtitle="Money taken at the counter or received in the mail."
             maxWidth="max-w-lg">
        <form method="POST" action="{{ route('bills.mark-paid', $record) }}" class="space-y-4" id="mark-paid-form">
            @csrf

            <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-700 ring-1 ring-slate-200">
                <span class="font-semibold">{{ $record->balanceFormatted() }}</span> is outstanding on bill {{ $record->reference }}.
            </div>

            <x-field label="Amount Received" for="paid_amount" required
                     hint="Part payments are allowed. Cannot exceed the outstanding balance."
                     :error="$errors->first('amount')">
                <x-input id="paid_amount" name="amount" inputmode="decimal" required
                         :value="old('amount', $balanceDecimal)" class="tabular" />
            </x-field>

            <x-field label="How It Was Paid" for="method" required :error="$errors->first('method')">
                <x-select id="method" name="method" required>
                    @foreach ($methods as $key => $label)
                        @if ($key !== 'card')
                            <option value="{{ $key }}" @selected($key === 'cash')>{{ $label }}</option>
                        @endif
                    @endforeach
                </x-select>
            </x-field>

            <x-field label="Notes" for="payment_notes" hint="Check number, receipt book reference, anything worth recording.">
                <x-input id="payment_notes" name="notes" maxlength="500" />
            </x-field>
        </form>

        <x-slot:footer>
            <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'mark-paid')">Cancel</x-button>
            <x-button size="sm" icon="check" type="submit" form="mark-paid-form">Record Payment</x-button>
        </x-slot:footer>
    </x-modal>

    {{-- Void confirm. Modal, never a native dialog. --}}
    <x-modal name="void-bill" title="Void This Bill?" icon="warning" tone="danger" maxWidth="max-w-md">
        Voiding stops residents being able to pay this bill online and removes it from the outstanding total.
        The bill and any payments already taken against it are kept for audit. This is recorded in the audit log.
        <x-slot:footer>
            <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'void-bill')">Cancel</x-button>
            <x-button variant="danger" size="sm" icon="x-circle" type="submit" form="void-bill-form">Void Bill</x-button>
        </x-slot:footer>
    </x-modal>
</x-layouts.app>
