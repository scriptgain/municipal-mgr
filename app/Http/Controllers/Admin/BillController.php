<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\BillType;
use App\Models\Constituent;
use App\Services\Payments\PaymentProcessor;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Staff management of bills.
 *
 * Every state-changing action here is audited: marking a bill paid at the
 * counter, voiding it, and importing a batch all move public money around, and
 * a clerk needs to be able to answer "who did that, and when" a year later.
 */
class BillController extends Controller
{
    public function index(Request $request)
    {
        $query = Bill::with(['type', 'constituent'])->search($request->query('q'));

        $status = $request->query('status', 'all');

        match ($status) {
            'outstanding' => $query->outstanding(),
            'overdue' => $query->overdue(),
            'paid' => $query->where('status', 'paid'),
            'void' => $query->where('status', 'void'),
            default => null,
        };

        if ($type = $request->query('type')) {
            $query->where('bill_type_id', $type);
        }

        $records = $query->latest()->paginate((int) config('municipal.rows_per_page', 25))->withQueryString();

        return view('admin.bills.index', [
            'records' => $records,
            'search' => $request->query('q'),
            'status' => $status,
            'selectedType' => $type,
            'types' => BillType::ordered()->get(),
            'counts' => [
                'all' => Bill::count(),
                'outstanding' => Bill::outstanding()->count(),
                'overdue' => Bill::overdue()->count(),
                'paid' => Bill::where('status', 'paid')->count(),
            ],
            'totals' => [
                'outstanding' => Money::format((int) Bill::outstanding()->sum('amount_cents') - (int) Bill::outstanding()->sum('amount_paid_cents')),
                'overdue' => Money::format((int) Bill::overdue()->sum('amount_cents') - (int) Bill::overdue()->sum('amount_paid_cents')),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.bills.create', [
            'record' => new Bill(['status' => 'unpaid']),
            'types' => BillType::active()->ordered()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $bill = new Bill($data);
        $bill->created_by = auth()->id();
        $bill->constituent_id = $this->matchConstituent($data);
        $bill->save();

        AuditLog::record('created', "Bill {$bill->reference} raised for {$bill->amountFormatted()}", $bill);

        return redirect()->route('bills.show', $bill)
            ->with('status', "Bill {$bill->reference} Created.");
    }

    public function show(Bill $bill)
    {
        $bill->load(['type', 'constituent', 'payments.recorder', 'creator']);

        return view('admin.bills.show', [
            'record' => $bill,
            'payments' => $bill->payments,
            'methods' => config('payments.methods'),
            'balanceDecimal' => Money::decimal($bill->balanceCents()),
        ]);
    }

    public function edit(Bill $bill)
    {
        return view('admin.bills.edit', [
            'record' => $bill,
            'types' => BillType::active()->ordered()->get(),
            'amountDecimal' => Money::decimal($bill->amount_cents),
        ]);
    }

    public function update(Request $request, Bill $bill)
    {
        $data = $this->validated($request, $bill);

        // Refuse to move the total below what has already been taken: that
        // would silently manufacture a credit nobody authorised.
        if ($data['amount_cents'] < $bill->amount_paid_cents) {
            return back()->withInput()->withErrors([
                'amount' => 'The Total Cannot Be Less Than The ' . $bill->paidFormatted() . ' Already Paid.',
            ]);
        }

        $bill->fill($data);
        $bill->constituent_id = $bill->constituent_id ?: $this->matchConstituent($data);
        $bill->save();
        $bill->recalculate();

        AuditLog::record('updated', "Bill {$bill->reference} updated", $bill);

        return redirect()->route('bills.show', $bill)->with('status', "Bill {$bill->reference} Saved.");
    }

    public function destroy(Bill $bill)
    {
        abort_unless(auth()->user()->isEditor(), 403);

        // A bill with money against it is never deleted: the payment record
        // must keep pointing at something. Void it instead.
        if ($bill->payments()->settled()->exists()) {
            return back()->withErrors([
                'bill' => 'That Bill Has Payments Against It. Void It Instead Of Deleting It.',
            ]);
        }

        $reference = $bill->reference;
        $bill->delete();

        AuditLog::record('deleted', "Bill {$reference} deleted");

        return redirect()->route('bills.index')->with('status', "Bill {$reference} Deleted.");
    }

    public function bulkDestroy(Request $request)
    {
        abort_unless(auth()->user()->isEditor(), 403);

        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));

        if (! $ids) {
            return back()->with('warning', 'No Rows Were Selected.');
        }

        $bills = Bill::whereIn('id', $ids)->withCount(['payments' => fn ($q) => $q->settled()])->get();

        $deleted = 0;
        $skipped = 0;

        foreach ($bills as $bill) {
            if ($bill->payments_count > 0) {
                $skipped++;
                continue;
            }
            $bill->delete();
            $deleted++;
        }

        AuditLog::record('bulk-deleted', "{$deleted} bill(s) deleted in bulk, {$skipped} skipped for having payments");

        $message = "{$deleted} Bill(s) Deleted.";
        if ($skipped) {
            $message .= " {$skipped} Skipped Because They Have Payments Against Them.";
        }

        return back()->with('status', $message);
    }

    /*
    |--------------------------------------------------------------------------
    | Counter actions
    |--------------------------------------------------------------------------
    */

    /** Record a payment taken at the counter or received in the mail. */
    public function markPaid(Request $request, Bill $bill)
    {
        abort_unless(auth()->user()->canEditContent(), 403);

        $data = $request->validate([
            'amount' => ['required', 'string', 'max:20'],
            'method' => ['required', 'string', 'in:' . implode(',', array_keys(config('payments.methods')))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $bill->isPayable()) {
            return back()->withErrors(['amount' => 'That Bill Is Not Open For Payment.']);
        }

        $amountCents = Money::parse($data['amount']);

        if ($amountCents === null || $amountCents < 1) {
            return back()->withErrors(['amount' => 'Enter A Valid Amount.']);
        }

        if ($amountCents > $bill->balanceCents()) {
            return back()->withErrors([
                'amount' => 'That Is More Than The ' . $bill->balanceFormatted() . ' Outstanding.',
            ]);
        }

        $payment = PaymentProcessor::recordOffline($bill, $amountCents, $data['method'], auth()->user(), $data['notes'] ?? null);

        AuditLog::record(
            'payment-recorded',
            "Offline payment {$payment->reference} of {$payment->amountFormatted()} ({$payment->methodLabel()}) recorded against bill {$bill->reference}",
            $payment
        );

        return back()->with('status', "Payment {$payment->reference} Recorded.");
    }

    /** Void a bill: it stops being payable and stops appearing as owed. */
    public function void(Request $request, Bill $bill)
    {
        abort_unless(auth()->user()->isEditor(), 403);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $bill->forceFill([
            'status' => 'void',
            'notes' => trim(($bill->notes ? $bill->notes . "\n\n" : '')
                . 'Voided ' . now()->format('Y-m-d H:i') . ' by ' . auth()->user()->name
                . ($data['reason'] ?? '' ? ': ' . $data['reason'] : '')),
        ])->save();

        AuditLog::record('voided', "Bill {$bill->reference} voided" . ($data['reason'] ?? '' ? ": {$data['reason']}" : ''), $bill);

        return back()->with('status', "Bill {$bill->reference} Voided.");
    }

    /** Put a voided bill back into collection. */
    public function reinstate(Bill $bill)
    {
        abort_unless(auth()->user()->isEditor(), 403);
        abort_unless($bill->status === 'void', 400);

        $bill->forceFill(['status' => 'unpaid'])->save();
        $bill->recalculate();

        AuditLog::record('reinstated', "Bill {$bill->reference} reinstated", $bill);

        return back()->with('status', "Bill {$bill->reference} Reinstated.");
    }

    /*
    |--------------------------------------------------------------------------
    | Import
    |--------------------------------------------------------------------------
    */

    public function importForm()
    {
        return view('admin.bills.import', [
            'types' => BillType::active()->ordered()->get(),
        ]);
    }

    /**
     * CSV import: the realistic way a utility billing run reaches this system.
     *
     * Parsed with the CSV functions in the standard library rather than a
     * package, and every row is validated. A row that fails is reported back
     * with its line number rather than silently skipped, because a clerk needs
     * to know that 4 of their 900 bills did not land.
     */
    public function import(Request $request)
    {
        abort_unless(auth()->user()->canEditContent(), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'bill_type_id' => ['required', 'integer', 'exists:bill_types,id'],
        ]);

        $type = BillType::findOrFail($request->integer('bill_type_id'));
        $handle = fopen($request->file('file')->getRealPath(), 'r');

        if (! $handle) {
            return back()->withErrors(['file' => 'That File Could Not Be Read.']);
        }

        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);

            return back()->withErrors(['file' => 'That File Appears To Be Empty.']);
        }

        $header = array_map(fn ($h) => Str::slug(trim((string) $h), '_'), $header);
        $created = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // Blank line.
            }

            $data = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), null));
            $amountCents = Money::parse($data['amount'] ?? null);

            if ($amountCents === null || $amountCents < 1) {
                $errors[] = "Line {$line}: missing or invalid amount.";
                continue;
            }

            if (count($errors) > 25) {
                $errors[] = 'Too many errors; import stopped.';
                break;
            }

            $bill = new Bill([
                'bill_type_id' => $type->id,
                'account_number' => $this->trimOrNull($data['account_number'] ?? null),
                'payer_name' => $this->trimOrNull($data['name'] ?? ($data['payer_name'] ?? null)),
                'payer_email' => $this->trimOrNull($data['email'] ?? null),
                'payer_phone' => $this->trimOrNull($data['phone'] ?? null),
                'lookup_surname' => $this->trimOrNull($data['last_name'] ?? ($data['surname'] ?? null)),
                'lookup_postal_code' => $this->trimOrNull($data['zip'] ?? ($data['postal_code'] ?? null)),
                'amount_cents' => $amountCents,
                'description' => $this->trimOrNull($data['description'] ?? null),
                'due_date' => $this->parseDate($data['due_date'] ?? null),
                'issued_on' => $this->parseDate($data['issued_on'] ?? null) ?? now()->toDateString(),
                'status' => 'unpaid',
            ]);

            $bill->created_by = auth()->id();
            $bill->constituent_id = $this->matchConstituent([
                'payer_email' => $bill->payer_email,
                'payer_phone' => $bill->payer_phone,
                'payer_name' => $bill->payer_name,
            ]);
            $bill->save();
            $created++;
        }

        fclose($handle);

        AuditLog::record('imported', "{$created} bill(s) imported as {$type->label}");

        return redirect()->route('bills.index')
            ->with('status', "{$created} Bill(s) Imported.")
            ->with('import_errors', $errors);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function validated(Request $request, ?Bill $bill = null): array
    {
        $data = $request->validate([
            'bill_type_id' => ['required', 'integer', 'exists:bill_types,id'],
            'account_number' => ['nullable', 'string', 'max:80'],
            'payer_name' => ['nullable', 'string', 'max:150'],
            'payer_email' => ['nullable', 'email', 'max:150'],
            'payer_phone' => ['nullable', 'string', 'max:40'],
            'lookup_surname' => ['nullable', 'string', 'max:80'],
            'lookup_postal_code' => ['nullable', 'string', 'max:20'],
            'amount' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'issued_on' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
        ]);

        $amountCents = Money::parse($data['amount']);

        // "24.50" is a string as far as the validator is concerned, so the
        // amount is checked here and failed as a normal validation error rather
        // than being allowed through as a zero.
        if ($amountCents === null || $amountCents < 1) {
            throw ValidationException::withMessages([
                'amount' => 'Enter A Valid Amount, For Example 124.50.',
            ]);
        }

        $data['amount_cents'] = $amountCents;
        unset($data['amount']);

        return $data;
    }

    /** Link the bill to a resident record when one clearly matches. */
    private function matchConstituent(array $data): ?int
    {
        $email = Constituent::emailKey($data['payer_email'] ?? null);
        $phone = Constituent::phoneKey($data['payer_phone'] ?? null);

        if (! $email && ! $phone) {
            return null;
        }

        $match = $email ? Constituent::where('email_key', $email)->first() : null;
        $match ??= $phone ? Constituent::where('phone_key', $phone)->first() : null;

        return $match?->id;
    }

    private function trimOrNull($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function parseDate($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return rescue(fn () => \Illuminate\Support\Carbon::parse($value)->toDateString(), null, false);
    }
}
