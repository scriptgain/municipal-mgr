<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillType;
use App\Models\Payment;
use App\Services\Payments\PaymentProcessor;
use App\Services\Payments\PaymentSettings;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Pay Your Bill — the resident-facing flow.
 *
 * Account-free by design, like Report An Issue: a resident paying a $58 water
 * bill will not create a password to do it.
 *
 * ENUMERATION: bills are never addressed by id or reference in a URL. A
 * successful lookup puts the bill id in the SESSION and redirects to a fixed
 * path, so there is no /pay/bill/1041 for anyone to walk. Lookup itself needs
 * the reference AND a second factor, and is rate limited on top.
 *
 * AMOUNTS: nothing on this controller reads an amount from the request for a
 * bill-backed payment. The one place a resident types an amount is the open
 * payment path, and it is validated against the bill type's bounds both here
 * and again inside PaymentProcessor.
 */
class PaymentController extends Controller
{
    private const SESSION_BILL = 'payments.bill_id';
    private const SESSION_PAYMENT = 'payments.payment_id';

    /*
    |--------------------------------------------------------------------------
    | Landing and lookup
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return view('site.pay.index', [
            'types' => BillType::active()->ordered()->get(),
            'openTypes' => BillType::openPayable()->ordered()->get(),
            'intro' => PaymentSettings::introText(),
            'isTestMode' => PaymentSettings::isTestMode(),
            'isReady' => PaymentSettings::isReady(),
            'supportEmail' => PaymentSettings::supportEmail(),
            'supportPhone' => PaymentSettings::supportPhone(),
        ]);
    }

    public function lookupForm(Request $request)
    {
        return view('site.pay.lookup', [
            'types' => BillType::active()->ordered()->get(),
            'selectedType' => $request->query('type'),
            'isTestMode' => PaymentSettings::isTestMode(),
            'isReady' => PaymentSettings::isReady(),
            'factors' => config('payments.lookup_factors'),
        ]);
    }

    /**
     * Find a bill by reference plus a second factor.
     *
     * The failure message is deliberately identical whether the reference does
     * not exist, the second factor is wrong, or the bill is already paid. A
     * distinct "no such bill" reply would turn this into an oracle for which
     * reference numbers are real.
     */
    public function lookup(Request $request)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:40'],
            'second_factor' => ['required', 'string', 'max:80'],
        ], [
            'second_factor.required' => 'Please Enter The Last Name Or ZIP Code On The Bill.',
        ]);

        // Second gate behind the route throttle, keyed per IP, so a distributed
        // scan still meets a per-source ceiling.
        $key = 'bill-lookup:' . $request->ip();
        $limit = (int) config('payments.lookup_rate_limit', 8);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return back()->withInput()->withErrors([
                'reference' => 'Too Many Attempts. Please Wait A Minute And Try Again.',
            ]);
        }
        RateLimiter::hit($key, 60);

        $bill = Bill::with('type')
            ->where('reference', trim($data['reference']))
            ->first();

        $generic = 'We Could Not Find A Bill Matching Those Details. Please Check The Reference Number And Try Again.';

        if (! $bill || ! $bill->matchesSecondFactor($data['second_factor'])) {
            return back()->withInput()->withErrors(['reference' => $generic]);
        }

        // Clear the limiter: a resident who found their own bill is not the
        // threat this counter exists for.
        RateLimiter::clear($key);

        if (! $bill->isPayable()) {
            return back()->withInput()->withErrors([
                'reference' => $bill->status === 'paid'
                    ? 'That Bill Has Already Been Paid In Full. Thank You.'
                    : $generic,
            ]);
        }

        $request->session()->put(self::SESSION_BILL, $bill->id);

        return redirect()->route('site.pay.review');
    }

    /*
    |--------------------------------------------------------------------------
    | Review and checkout
    |--------------------------------------------------------------------------
    */

    public function review(Request $request)
    {
        $bill = $this->sessionBill($request);

        if (! $bill) {
            return redirect()->route('site.pay.lookup')
                ->withErrors(['reference' => 'Please Look Up Your Bill Again.']);
        }

        return view('site.pay.review', [
            'bill' => $bill,
            'isTestMode' => PaymentSettings::isTestMode(),
            'isReady' => PaymentSettings::isReady(),
            'supportEmail' => PaymentSettings::supportEmail(),
            'supportPhone' => PaymentSettings::supportPhone(),
        ]);
    }

    /** Start a bill payment. The amount is the bill's balance, server side. */
    public function startBillPayment(Request $request)
    {
        $bill = $this->sessionBill($request);

        if (! $bill) {
            return redirect()->route('site.pay.lookup');
        }

        $data = $request->validate([
            'payer_name' => ['nullable', 'string', 'max:150'],
            'payer_email' => ['nullable', 'email', 'max:150'],
        ]);

        $result = PaymentProcessor::startBillPayment($bill, [
            'name' => $data['payer_name'] ?? null,
            'email' => $data['payer_email'] ?? null,
        ], $request->ip());

        if (! $result['ok']) {
            return back()->withErrors(['payment' => $result['error']]);
        }

        $request->session()->put(self::SESSION_PAYMENT, $result['payment']->id);

        return redirect()->route('site.pay.checkout');
    }

    /*
    |--------------------------------------------------------------------------
    | Open payments (no bill reference)
    |--------------------------------------------------------------------------
    */

    public function openForm(string $type)
    {
        $billType = BillType::openPayable()->where('key', $type)->firstOrFail();

        return view('site.pay.open', [
            'type' => $billType,
            'minLabel' => Money::format($billType->minCents()),
            'maxLabel' => Money::format($billType->maxCents()),
            'minDecimal' => Money::decimal($billType->minCents()),
            'maxDecimal' => Money::decimal($billType->maxCents()),
            'isTestMode' => PaymentSettings::isTestMode(),
            'isReady' => PaymentSettings::isReady(),
        ]);
    }

    public function startOpenPayment(Request $request, string $type)
    {
        $billType = BillType::openPayable()->where('key', $type)->firstOrFail();

        $data = $request->validate([
            'amount' => ['required', 'string', 'max:20'],
            'payer_name' => ['required', 'string', 'max:150'],
            'payer_email' => ['required', 'email', 'max:150'],
            'payer_phone' => ['nullable', 'string', 'max:40'],
            'memo' => ['nullable', 'string', 'max:200'],
            // Honeypot: bots fill it, humans never see it.
            'website' => ['nullable', 'size:0'],
        ]);

        $amountCents = Money::parse($data['amount']);

        if ($amountCents === null || $amountCents < $billType->minCents() || $amountCents > $billType->maxCents()) {
            return back()->withInput()->withErrors([
                'amount' => 'Please Enter An Amount Between ' . Money::format($billType->minCents())
                    . ' And ' . Money::format($billType->maxCents()) . '.',
            ]);
        }

        $result = PaymentProcessor::startOpenPayment($billType, $amountCents, [
            'name' => $data['payer_name'],
            'email' => $data['payer_email'],
            'phone' => $data['payer_phone'] ?? null,
            'memo' => $data['memo'] ?? null,
        ], $request->ip());

        if (! $result['ok']) {
            return back()->withInput()->withErrors(['amount' => $result['error']]);
        }

        $request->session()->put(self::SESSION_PAYMENT, $result['payment']->id);

        return redirect()->route('site.pay.checkout');
    }

    /*
    |--------------------------------------------------------------------------
    | Card form
    |--------------------------------------------------------------------------
    */

    public function checkout(Request $request)
    {
        $payment = $this->sessionPayment($request);

        if (! $payment) {
            return redirect()->route('site.pay.index');
        }

        $secret = PaymentProcessor::clientSecret($payment);

        // Already paid, quite possibly by the webhook arriving before the
        // resident got back to us. Send them to the receipt, not the card form.
        if ($secret['settled']) {
            return redirect()->route('site.pay.receipt', $payment->receipt_token);
        }

        if (! $secret['ok']) {
            return redirect()->route('site.pay.index')->withErrors(['payment' => $secret['error']]);
        }

        return view('site.pay.checkout', [
            'payment' => $payment,
            'bill' => $payment->bill,
            'clientSecret' => $secret['client_secret'],
            'publishableKey' => PaymentSettings::publishableKey(),
            'connectAccountId' => PaymentSettings::connectAccountId(),
            'returnUrl' => route('site.pay.complete', $payment->receipt_token),
            'isTestMode' => PaymentSettings::isTestMode(),
            'amountLabel' => $payment->amountFormatted(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Completion and receipt
    |--------------------------------------------------------------------------
    */

    /**
     * Where Stripe sends the resident back to.
     *
     * The webhook may already have settled this payment, or may not arrive for
     * another few seconds. Both are normal. We sync on arrival, and the receipt
     * view renders a "still processing" state rather than claiming failure for
     * a payment that simply has not been confirmed yet.
     */
    public function complete(Request $request, string $token)
    {
        $payment = Payment::where('receipt_token', $token)->firstOrFail();

        if (! $payment->isSettled()) {
            PaymentProcessor::syncFromStripe($payment);
        }

        // The flow is over either way: drop the working state so a back button
        // cannot restart a checkout for a bill that is now paid.
        $request->session()->forget([self::SESSION_BILL, self::SESSION_PAYMENT]);

        return redirect()->route('site.pay.receipt', $payment->receipt_token);
    }

    /**
     * The receipt. The token in the URL is the credential, exactly as the
     * tracking token is for a service request.
     */
    public function receipt(string $token)
    {
        $payment = Payment::with(['bill.type', 'type'])
            ->where('receipt_token', $token)
            ->firstOrFail();

        // A pending payment that has been pending a while is worth one more
        // look: the resident may have refreshed before the webhook landed.
        if ($payment->status === 'pending' && $payment->created_at->lt(now()->subSeconds(3))) {
            $payment = PaymentProcessor::syncFromStripe($payment);
        }

        return view('site.pay.receipt', [
            'payment' => $payment,
            'bill' => $payment->bill,
            'isTestMode' => ! $payment->livemode && $payment->method === 'card',
            'supportEmail' => PaymentSettings::supportEmail(),
            'supportPhone' => PaymentSettings::supportPhone(),
            'siteName' => \App\Services\SiteSettings::formalName(),
        ]);
    }

    /** Printable receipt, served as a download. */
    public function downloadReceipt(string $token)
    {
        $payment = Payment::with(['bill.type', 'type'])
            ->where('receipt_token', $token)
            ->firstOrFail();

        abort_unless($payment->isSettled(), 404);

        $html = view('site.pay.receipt-print', [
            'payment' => $payment,
            'bill' => $payment->bill,
            'siteName' => \App\Services\SiteSettings::formalName(),
            'supportEmail' => PaymentSettings::supportEmail(),
            'supportPhone' => PaymentSettings::supportPhone(),
            'isTestMode' => ! $payment->livemode && $payment->method === 'card',
        ])->render();

        $filename = 'receipt-' . Str::lower($payment->reference) . '.html';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Session helpers
    |--------------------------------------------------------------------------
    */

    private function sessionBill(Request $request): ?Bill
    {
        $id = $request->session()->get(self::SESSION_BILL);

        if (! $id) {
            return null;
        }

        $bill = Bill::with('type')->find($id);

        // Re-check payability on every read: staff may have voided it, or
        // another payment may have cleared it, since the lookup.
        return $bill && $bill->isPayable() ? $bill : null;
    }

    private function sessionPayment(Request $request): ?Payment
    {
        $id = $request->session()->get(self::SESSION_PAYMENT);

        return $id ? Payment::with('bill')->find($id) : null;
    }
}
