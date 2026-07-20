<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Services\Payments\PaymentProcessor;
use App\Services\Payments\PaymentSettings;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Staff view of money received, plus refunds and reconciliation.
 *
 * Refunds are audited unconditionally, including the amount and who authorised
 * them. Sending public money back out is exactly the action an auditor will
 * ask about.
 */
class PaymentAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['bill', 'type', 'constituent'])->search($request->query('q'));

        $status = $request->query('status', 'all');

        match ($status) {
            'succeeded' => $query->where('status', 'succeeded'),
            'pending' => $query->where('status', 'pending'),
            'failed' => $query->whereIn('status', ['failed', 'canceled']),
            'refunded' => $query->whereIn('status', ['refunded', 'partially_refunded']),
            default => null,
        };

        if ($method = $request->query('method')) {
            $query->where('method', $method);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $records = $query->latest()->paginate((int) config('municipal.rows_per_page', 25))->withQueryString();

        return view('admin.payments.index', [
            'records' => $records,
            'search' => $request->query('q'),
            'status' => $status,
            'method' => $method,
            'from' => $from,
            'to' => $to,
            'methods' => config('payments.methods'),
            'counts' => [
                'all' => Payment::count(),
                'succeeded' => Payment::where('status', 'succeeded')->count(),
                'pending' => Payment::where('status', 'pending')->count(),
                'failed' => Payment::whereIn('status', ['failed', 'canceled'])->count(),
                'refunded' => Payment::whereIn('status', ['refunded', 'partially_refunded'])->count(),
            ],
            'isTestMode' => PaymentSettings::isTestMode(),
        ]);
    }

    public function show(Payment $payment)
    {
        $payment->load(['bill.type', 'type', 'constituent', 'recorder']);

        return view('admin.payments.show', [
            'record' => $payment,
            'refundableDecimal' => Money::decimal($payment->refundableCents()),
            'refundableLabel' => Money::format($payment->refundableCents()),
            'receiptUrl' => route('site.pay.receipt', $payment->receipt_token),
        ]);
    }

    /**
     * Initiate a refund. Partial or full.
     *
     * The maximum is computed from the payment record, never from the form, so
     * a tampered field cannot refund more than was ever taken.
     */
    public function refund(Request $request, Payment $payment)
    {
        abort_unless(auth()->user()->isEditor(), 403);

        $data = $request->validate([
            'amount' => ['nullable', 'string', 'max:20'],
            'reason' => ['nullable', 'string', 'in:duplicate,fraudulent,requested_by_customer'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $payment->isRefundable()) {
            return back()->withErrors(['amount' => 'That Payment Cannot Be Refunded.']);
        }

        // Blank amount means refund everything still refundable.
        $amountCents = trim((string) ($data['amount'] ?? '')) === ''
            ? null
            : Money::parse($data['amount']);

        if ($amountCents !== null && $amountCents < 1) {
            return back()->withErrors(['amount' => 'Enter A Valid Refund Amount.']);
        }

        if ($amountCents !== null && $amountCents > $payment->refundableCents()) {
            return back()->withErrors([
                'amount' => 'That Is More Than The ' . Money::format($payment->refundableCents()) . ' Still Refundable.',
            ]);
        }

        $result = PaymentProcessor::refund($payment, $amountCents, $data['reason'] ?? null);

        if (! $result['ok']) {
            return back()->withErrors(['amount' => $result['error']]);
        }

        $refunded = $amountCents === null ? $payment->refundableCents() : $amountCents;

        AuditLog::record(
            'refunded',
            "Refund of " . Money::format($refunded) . " issued on payment {$payment->reference}"
                . ($data['note'] ?? '' ? ": {$data['note']}" : ''),
            $payment
        );

        return back()->with('status', 'Refund Of ' . Money::format($refunded) . ' Issued.');
    }

    /*
    |--------------------------------------------------------------------------
    | Reconciliation
    |--------------------------------------------------------------------------
    */

    /**
     * Payments by day, with the Stripe payout each day's card takings settled
     * into. This is the screen a finance clerk reconciles the bank statement
     * against, so card and offline takings are separated: only the card total
     * will ever appear as a Stripe deposit.
     */
    public function reconciliation(Request $request)
    {
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $rows = Payment::query()
            ->settled()
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->select([
                DB::raw('DATE(paid_at) as day'),
                DB::raw('COUNT(*) as payment_count'),
                DB::raw('SUM(amount_cents) as gross_cents'),
                DB::raw('SUM(refunded_cents) as refunded_cents'),
                DB::raw("SUM(CASE WHEN method = 'card' THEN amount_cents ELSE 0 END) as card_cents"),
                DB::raw("SUM(CASE WHEN method != 'card' THEN amount_cents ELSE 0 END) as offline_cents"),
            ])
            ->groupBy('day')
            ->orderByDesc('day')
            ->get();

        // Payout references per day, collapsed to a readable list. A day's card
        // takings usually settle as one payout but can split across two.
        $payouts = Payment::query()
            ->settled()
            ->where('method', 'card')
            ->whereNotNull('stripe_payout_id')
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->select([
                DB::raw('DATE(paid_at) as day'),
                'stripe_payout_id',
                DB::raw('MAX(payout_arrival_at) as arrival'),
            ])
            ->groupBy('day', 'stripe_payout_id')
            ->get()
            ->groupBy('day');

        $days = $rows->map(function ($row) use ($payouts) {
            $net = (int) $row->gross_cents - (int) $row->refunded_cents;
            $dayPayouts = $payouts->get($row->day, collect());

            return [
                'day' => $row->day,
                'label' => \Illuminate\Support\Carbon::parse($row->day)->format(config('municipal.date_format')),
                'count' => (int) $row->payment_count,
                'gross' => Money::format((int) $row->gross_cents),
                'refunded' => Money::format((int) $row->refunded_cents),
                'net' => Money::format($net),
                'card' => Money::format((int) $row->card_cents),
                'offline' => Money::format((int) $row->offline_cents),
                'payouts' => $dayPayouts->map(fn ($p) => [
                    'id' => $p->stripe_payout_id,
                    'arrival' => $p->arrival ? \Illuminate\Support\Carbon::parse($p->arrival)->format(config('municipal.date_format')) : null,
                ])->all(),
                'awaiting_payout' => (int) $row->card_cents > 0 && $dayPayouts->isEmpty(),
            ];
        });

        $totalGross = (int) $rows->sum('gross_cents');
        $totalRefunded = (int) $rows->sum('refunded_cents');

        return view('admin.payments.reconciliation', [
            'days' => $days,
            'from' => $from,
            'to' => $to,
            'summary' => [
                'gross' => Money::format($totalGross),
                'refunded' => Money::format($totalRefunded),
                'net' => Money::format($totalGross - $totalRefunded),
                'card' => Money::format((int) $rows->sum('card_cents')),
                'offline' => Money::format((int) $rows->sum('offline_cents')),
                'count' => (int) $rows->sum('payment_count'),
            ],
            'isTestMode' => PaymentSettings::isTestMode(),
        ]);
    }

    /** CSV export of the reconciliation range, for the finance system. */
    public function exportReconciliation(Request $request)
    {
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $payments = Payment::with('bill')
            ->settled()
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->orderBy('paid_at')
            ->get();

        $filename = "payments-{$from}-to-{$to}.csv";

        return response()->streamDownload(function () use ($payments) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Payment Reference', 'Date Paid', 'Bill Reference', 'Payer',
                'Method', 'Status', 'Amount', 'Refunded', 'Net',
                'Stripe Payment Intent', 'Stripe Payout', 'Live Mode',
            ]);

            foreach ($payments as $payment) {
                fputcsv($out, [
                    $payment->reference,
                    $payment->paid_at?->toDateTimeString(),
                    $payment->bill?->reference,
                    $payment->payer_name,
                    $payment->methodLabel(),
                    $payment->statusLabel(),
                    Money::decimal($payment->amount_cents),
                    Money::decimal($payment->refunded_cents),
                    Money::decimal($payment->amount_cents - $payment->refunded_cents),
                    $payment->stripe_payment_intent_id,
                    $payment->stripe_payout_id,
                    $payment->livemode ? 'live' : 'TEST',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk
    |--------------------------------------------------------------------------
    */

    /**
     * massSelect bulk delete.
     *
     * Only ever removes failed, canceled and abandoned-pending rows. A
     * successful payment is a financial record and is not deletable from a
     * table checkbox, whoever is signed in.
     */
    public function bulkDestroy(Request $request)
    {
        abort_unless(auth()->user()->isEditor(), 403);

        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));

        if (! $ids) {
            return back()->with('warning', 'No Rows Were Selected.');
        }

        $deletable = Payment::whereIn('id', $ids)
            ->whereIn('status', ['failed', 'canceled', 'pending'])
            ->get();

        $skipped = count($ids) - $deletable->count();
        $deleted = 0;

        foreach ($deletable as $payment) {
            $payment->delete();
            $deleted++;
        }

        AuditLog::record('bulk-deleted', "{$deleted} unsuccessful payment record(s) deleted in bulk");

        $message = "{$deleted} Payment Record(s) Deleted.";
        if ($skipped > 0) {
            $message .= " {$skipped} Skipped: Successful Payments Cannot Be Deleted.";
        }

        return back()->with('status', $message);
    }
}
