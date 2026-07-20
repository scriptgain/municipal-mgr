<x-layouts.app title="Bills">
    <x-page-header title="Bills" icon="database"
                   subtitle="Utility bills, permit fees, citations and anything else the town invoices for.">
        <x-slot:actions>
            <x-button variant="secondary" icon="upload" :href="route('bills.import')">Import Bills</x-button>
            <x-button icon="plus" :href="route('bills.create')">Raise A Bill</x-button>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-800 ring-1 ring-brand-200">{{ session('status') }}</div>
    @endif
    @if (session('warning'))
        <div class="mb-5 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">{{ session('warning') }}</div>
    @endif
    @if (session('import_errors'))
        <div class="mb-5 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">
            <p class="font-semibold">Some Rows Were Not Imported</p>
            <ul class="mt-1.5 list-disc space-y-0.5 pl-5">
                @foreach (session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @error('bill')
        <div class="mb-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-rose-200">{{ $message }}</div>
    @enderror

    {{-- Money at a glance --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Outstanding" :value="$totals['outstanding']" icon="database" />
        <x-stat label="Overdue" :value="$totals['overdue']" icon="warning" />
        <x-stat label="Bills Outstanding" :value="$counts['outstanding']" icon="file-text" />
        <x-stat label="Bills Paid" :value="$counts['paid']" icon="check-circle" />
    </div>

    <div class="section-divider mb-6"></div>

    {{-- Status filter. Tabs rather than a long scrolling list of everything. --}}
    <nav class="mb-5 flex flex-wrap items-center gap-2" aria-label="Filter Bills By Status">
        @foreach ([
            'all' => 'All Bills',
            'outstanding' => 'Outstanding',
            'overdue' => 'Overdue',
            'paid' => 'Paid',
            'void' => 'Void',
        ] as $key => $label)
            <a href="{{ route('bills.index', array_filter(['status' => $key, 'q' => $search, 'type' => $selectedType])) }}"
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
            {{-- Search and type filter --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3">
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <label class="sr-only" for="q">Search Bills</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 w-4 h-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                        <input id="q" name="q" type="search" value="{{ $search }}"
                               placeholder="Reference, account, name or email…"
                               class="w-72 rounded-lg border-0 py-2 pl-9 pr-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-600">
                    </div>
                    <label class="sr-only" for="type">Bill Type</label>
                    <select id="type" name="type"
                            class="rounded-lg border-0 py-2 pl-3 pr-8 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                        <option value="">All Types</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" @selected((string) $selectedType === (string) $type->id)>{{ $type->label }}</option>
                        @endforeach
                    </select>
                    <x-button type="submit" variant="secondary" size="sm">Search</x-button>
                    @if ($search || $selectedType)
                        <x-button variant="ghost" size="sm" :href="route('bills.index', ['status' => $status])">Clear</x-button>
                    @endif
                </form>
                <x-button :href="route('bills.create')" icon="plus" size="sm">Raise A Bill</x-button>
            </div>

            <x-bulk-bar :action="route('bills.bulk-destroy')" label="Bill" modal="bulk-delete-bills" />

            @if ($records->count())
                <x-table flush>
                    <caption class="sr-only">Bills</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="w-12"><x-select-all /></th>
                            <th scope="col">Reference</th>
                            <th scope="col">Billed To</th>
                            <th scope="col">Type</th>
                            <th scope="col" class="text-right">Amount</th>
                            <th scope="col" class="text-right">Balance</th>
                            <th scope="col">Due</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $bill)
                            <tr>
                                <td><x-select-row :id="$bill->id" :label="'Bill ' . $bill->reference" /></td>
                                <td>
                                    <a href="{{ route('bills.show', $bill) }}" class="font-medium tabular text-slate-900 hover:text-brand-700">{{ $bill->reference }}</a>
                                    @if ($bill->account_number)
                                        <span class="block text-xs tabular text-slate-500">Acct {{ $bill->account_number }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($bill->constituent)
                                        <a href="{{ route('constituents.show', $bill->constituent) }}" class="text-slate-900 hover:text-brand-700">{{ $bill->payer_name ?: $bill->constituent->name }}</a>
                                    @else
                                        <span class="text-slate-700">{{ $bill->payer_name ?: 'Not Recorded' }}</span>
                                    @endif
                                    @if ($bill->payer_email)
                                        <span class="block text-xs text-slate-500">{{ $bill->payer_email }}</span>
                                    @endif
                                </td>
                                <td class="text-slate-600">{{ $bill->type?->label }}</td>
                                <td class="text-right tabular">{{ $bill->amountFormatted() }}</td>
                                <td class="text-right tabular font-medium {{ $bill->balanceCents() > 0 ? 'text-slate-900' : 'text-emerald-700' }}">
                                    {{ $bill->balanceFormatted() }}
                                </td>
                                <td class="text-slate-500">
                                    {{ $bill->due_date?->format(config('municipal.date_format')) ?? ': ' }}
                                </td>
                                <td>
                                    <x-badge :color="$bill->statusColor()" dot>
                                        {{ $bill->isOverdue() ? 'Overdue' : $bill->statusLabel() }}
                                    </x-badge>
                                </td>
                                <td class="text-right">
                                    <x-admin.row-actions :edit="route('bills.edit', $bill)"
                                                         :delete="route('bills.destroy', $bill)"
                                                         :name="'delete-bill-' . $bill->id"
                                                         title="Delete This Bill?"
                                                         message="This permanently removes the bill. Bills that already have payments against them cannot be deleted; void them instead." />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @else
                <x-admin.empty icon="database" title="No Bills Here Yet"
                               message="Raise a bill by hand, or import a billing run from a CSV file."
                               :href="route('bills.create')" label="Raise A Bill" />
            @endif
        </div>

        @if ($records->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $records->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
