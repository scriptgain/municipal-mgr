<x-layouts.app title="Reconciliation">
    <x-page-header title="Reconciliation" icon="scale"
                   subtitle="Payments by day, matched to the Stripe payout that landed in the town's bank account.">
        <x-slot:actions>
            <x-button variant="secondary" icon="chevron-left" :href="route('payments.index')">Back To Payments</x-button>
            <x-button variant="secondary" icon="download"
                      :href="route('payments.reconciliation.export', ['from' => $from, 'to' => $to])">Export CSV</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($isTestMode)
        <div role="status" class="mb-5 flex items-start gap-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">
            <x-icon name="warning" class="mt-0.5 w-4 h-4 shrink-0" aria-hidden="true" />
            <p>
                <span class="font-semibold">Test Mode.</span>
                Card figures below are test payments. Nothing here will match a real bank statement.
            </p>
        </div>
    @endif

    {{-- Range --}}
    <x-card class="mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-sm font-medium text-slate-700" for="from">From</label>
                <x-input id="from" name="from" type="date" :value="$from" class="mt-1.5" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="to">To</label>
                <x-input id="to" name="to" type="date" :value="$to" class="mt-1.5" />
            </div>
            <x-button type="submit" variant="secondary">Update Range</x-button>
        </form>
    </x-card>

    {{-- Totals for the range --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <x-stat label="Gross Received" :value="$summary['gross']" icon="database" />
        <x-stat label="Refunded" :value="$summary['refunded']" icon="restore" />
        <x-stat label="Net" :value="$summary['net']" icon="scale" />
        <x-stat label="Card (Online)" :value="$summary['card']" icon="bolt" />
        <x-stat label="Counter And Mail" :value="$summary['offline']" icon="building" />
    </div>

    <div class="section-divider my-6"></div>

    <x-card flush title="Daily Breakdown"
            subtitle="Only the card column ever appears as a Stripe deposit. Counter and mail takings are banked by staff and will not match a payout.">
        @if ($days->count())
            <x-table flush>
                <caption class="sr-only">Payments By Day, {{ $from }} To {{ $to }}</caption>
                <thead>
                    <tr>
                        <th scope="col">Day</th>
                        <th scope="col" class="text-right">Payments</th>
                        <th scope="col" class="text-right">Gross</th>
                        <th scope="col" class="text-right">Refunded</th>
                        <th scope="col" class="text-right">Net</th>
                        <th scope="col" class="text-right">Card</th>
                        <th scope="col" class="text-right">Counter And Mail</th>
                        <th scope="col">Stripe Payout</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($days as $day)
                        <tr>
                            <td class="font-medium text-slate-900">{{ $day['label'] }}</td>
                            <td class="text-right tabular">{{ $day['count'] }}</td>
                            <td class="text-right tabular">{{ $day['gross'] }}</td>
                            <td class="text-right tabular {{ $day['refunded'] !== '$0.00' ? 'text-rose-700' : 'text-slate-400' }}">{{ $day['refunded'] }}</td>
                            <td class="text-right tabular font-medium text-slate-900">{{ $day['net'] }}</td>
                            <td class="text-right tabular">{{ $day['card'] }}</td>
                            <td class="text-right tabular">{{ $day['offline'] }}</td>
                            <td>
                                @forelse ($day['payouts'] as $payout)
                                    <span class="block">
                                        <x-status-dot color="success" />
                                        <span class="font-mono text-xs text-slate-600">{{ $payout['id'] }}</span>
                                        @if ($payout['arrival'])
                                            <span class="block text-xs text-slate-400">Arrived {{ $payout['arrival'] }}</span>
                                        @endif
                                    </span>
                                @empty
                                    @if ($day['awaiting_payout'])
                                        <x-badge color="warn" dot>Awaiting Payout</x-badge>
                                    @else
                                        <span class="text-xs text-slate-400">No Card Takings</span>
                                    @endif
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @else
            <x-admin.empty icon="scale" title="No Payments In This Range"
                           message="Widen the date range, or check back once residents have started paying." />
        @endif
    </x-card>

    <x-card class="mt-6" title="How To Use This Screen">
        <div class="grid gap-6 sm:grid-cols-2">
            <div class="flex items-start gap-3">
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 ring-1 ring-brand-200">
                    <x-icon name="bolt" class="w-4 h-4" aria-hidden="true" />
                </span>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-slate-900">Card Takings</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        Settle into the town's Stripe account and are paid out on your Stripe payout schedule.
                        Match the payout reference here against the deposit on your bank statement. Stripe's fees
                        are deducted before the deposit, so the deposit will be slightly less than the card column.
                    </p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 ring-1 ring-brand-200">
                    <x-icon name="building" class="w-4 h-4" aria-hidden="true" />
                </span>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-slate-900">Counter And Mail Takings</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        Recorded by staff against a bill. These never pass through Stripe, so they will never carry
                        a payout reference. Reconcile them against your own cash and check deposits.
                    </p>
                </div>
            </div>
        </div>
    </x-card>
</x-layouts.app>
