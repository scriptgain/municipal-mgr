<x-layouts.app title="Payments Received">
    <x-page-header title="Payments Received" icon="database"
                   subtitle="Every payment taken online, at the counter and by mail.">
        <x-slot:actions>
            <x-button variant="secondary" icon="scale" :href="route('payments.reconciliation')">Reconciliation</x-button>
            <x-button variant="secondary" icon="database" :href="route('bills.index')">Bills</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($isTestMode)
        <div role="status" class="mb-5 flex items-start gap-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">
            <x-icon name="warning" class="mt-0.5 w-4 h-4 shrink-0" aria-hidden="true" />
            <p>
                <span class="font-semibold">Payments Are In Test Mode.</span>
                Card payments taken now are not real money and will never appear in your bank account.
                Test payments are marked in the table below.
            </p>
        </div>
    @endif

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-800 ring-1 ring-brand-200">{{ session('status') }}</div>
    @endif
    @if (session('warning'))
        <div class="mb-5 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">{{ session('warning') }}</div>
    @endif

    {{-- Status filter --}}
    <nav class="mb-5 flex flex-wrap items-center gap-2" aria-label="Filter Payments By Status">
        @foreach ([
            'all' => 'All Payments',
            'succeeded' => 'Succeeded',
            'pending' => 'Pending',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
        ] as $key => $label)
            <a href="{{ route('payments.index', array_filter(['status' => $key, 'q' => $search, 'method' => $method, 'from' => $from, 'to' => $to])) }}"
               @if ($status === $key) aria-current="page" @endif
               class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium transition {{ $status === $key ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-200 hover:bg-slate-50' }}">
                {{ $label }}
                @isset ($counts[$key])
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold tabular {{ $status === $key ? 'bg-white/20' : 'bg-slate-100 text-slate-600' }}">{{ $counts[$key] }}</span>
                @endisset
            </a>
        @endforeach
    </nav>

    <x-card flush>
        <div x-data="{{ bulk_state($records->pluck('id')) }}">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3">
                <form method="GET" class="flex flex-wrap items-end gap-2">
                    <input type="hidden" name="status" value="{{ $status }}">

                    <div>
                        <label class="sr-only" for="q">Search Payments</label>
                        <div class="relative">
                            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 w-4 h-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                            <input id="q" name="q" type="search" value="{{ $search }}"
                                   placeholder="Reference, payer or bill…"
                                   class="w-60 rounded-lg border-0 py-2 pl-9 pr-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-600">
                        </div>
                    </div>

                    <div>
                        <label class="sr-only" for="method">Payment Method</label>
                        <select id="method" name="method"
                                class="rounded-lg border-0 py-2 pl-3 pr-8 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                            <option value="">All Methods</option>
                            @foreach ($methods as $key => $label)
                                <option value="{{ $key }}" @selected($method === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500" for="from">From</label>
                        <input id="from" name="from" type="date" value="{{ $from }}"
                               class="rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500" for="to">To</label>
                        <input id="to" name="to" type="date" value="{{ $to }}"
                               class="rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                    </div>

                    <x-button type="submit" variant="secondary" size="sm">Filter</x-button>
                    @if ($search || $method || $from || $to)
                        <x-button variant="ghost" size="sm" :href="route('payments.index', ['status' => $status])">Clear</x-button>
                    @endif
                </form>
            </div>

            <x-bulk-bar :action="route('payments.bulk-destroy')" label="Payment" modal="bulk-delete-payments" />

            @if ($records->count())
                <x-table flush>
                    <caption class="sr-only">Payments Received</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="w-12"><x-select-all /></th>
                            <th scope="col">Reference</th>
                            <th scope="col">Payer</th>
                            <th scope="col">For</th>
                            <th scope="col">Method</th>
                            <th scope="col" class="text-right">Amount</th>
                            <th scope="col">Status</th>
                            <th scope="col">Date</th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $payment)
                            <tr>
                                <td><x-select-row :id="$payment->id" :label="'Payment ' . $payment->reference" /></td>
                                <td>
                                    <a href="{{ route('payments.show', $payment) }}" class="font-medium tabular text-slate-900 hover:text-brand-700">{{ $payment->reference }}</a>
                                    @if ($payment->isTestPayment())
                                        <x-badge color="warn" class="ml-1.5">Test</x-badge>
                                    @endif
                                </td>
                                <td>
                                    @if ($payment->constituent)
                                        <a href="{{ route('constituents.show', $payment->constituent) }}" class="text-slate-900 hover:text-brand-700">{{ $payment->payer_name ?: $payment->constituent->name }}</a>
                                    @else
                                        <span class="text-slate-700">{{ $payment->payer_name ?: 'Not Recorded' }}</span>
                                    @endif
                                </td>
                                <td class="text-slate-600">
                                    @if ($payment->bill)
                                        <a href="{{ route('bills.show', $payment->bill) }}" class="tabular hover:text-brand-700">{{ $payment->bill->reference }}</a>
                                    @else
                                        {{ $payment->type?->label ?? 'Payment' }}
                                    @endif
                                </td>
                                <td class="text-slate-600">{{ $payment->instrumentLabel() }}</td>
                                <td class="text-right tabular font-medium text-slate-900">
                                    {{ $payment->amountFormatted() }}
                                    @if ($payment->refunded_cents > 0)
                                        <span class="block text-xs font-normal text-slate-500">- {{ $payment->refundedFormatted() }}</span>
                                    @endif
                                </td>
                                <td><x-badge :color="$payment->statusColor()" dot>{{ $payment->statusLabel() }}</x-badge></td>
                                <td class="text-slate-500">{{ ($payment->paid_at ?? $payment->created_at)->format(config('municipal.date_format')) }}</td>
                                <td class="text-right">
                                    <x-button size="sm" variant="secondary" :href="route('payments.show', $payment)">Open</x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @else
                <x-admin.empty icon="database" title="No Payments Yet"
                               message="Payments will appear here as residents pay online and as staff record counter takings." />
            @endif
        </div>

        @if ($records->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $records->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
