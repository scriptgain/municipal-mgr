<?php

namespace App\Services\Payments;

use App\Mail\PaymentReceipt;
use App\Models\Bill;
use App\Models\BillType;
use App\Models\Constituent;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * The write path for money.
 *
 * Two rules govern everything in this class:
 *
 *  1. THE AMOUNT IS NEVER TAKEN FROM THE CLIENT. A bill-backed payment charges
 *     $bill->balanceCents(), read from the database at charge time. An open
 *     payment is the single exception, and it is re-validated and clamped
 *     against the bill type's bounds here, server side, not merely in the form
 *     request that reached the controller.
 *
 *  2. APPLYING A RESULT IS IDEMPOTENT. markSucceeded() may be called by the
 *     browser redirect and by the webhook, in either order, more than once. It
 *     takes a row lock, checks the current state and returns early if the work
 *     is already done. That is what stops a resident being charged or credited
 *     twice.
 */
class PaymentProcessor
{
    /**
     * Reuse window for an unfinished PaymentIntent. A resident who reloads the
     * card form gets the same intent back rather than a fresh one each time,
     * which keeps abandoned intents out of the municipality's dashboard.
     */
    private const REUSE_MINUTES = 60;

    /*
    |--------------------------------------------------------------------------
    | Starting a payment
    |--------------------------------------------------------------------------
    */

    /**
     * Begin paying a bill.
     *
     * @return array{ok: bool, payment: ?Payment, client_secret: ?string, error: ?string}
     */
    public static function startBillPayment(Bill $bill, array $payer, ?string $ip = null): array
    {
        if (! $bill->isPayable()) {
            return self::fail('That bill is not open for payment.');
        }

        // The one true amount. Not a form field, not a query string.
        $amountCents = $bill->balanceCents();

        if ($amountCents < 50) {
            return self::fail('That balance is too small to pay by card. Please contact the office.');
        }

        if ($existing = self::reusablePayment($bill->id, $amountCents)) {
            return self::resume($existing);
        }

        $payment = Payment::create([
            'bill_id' => $bill->id,
            'bill_type_id' => $bill->bill_type_id,
            'constituent_id' => $bill->constituent_id,
            'amount_cents' => $amountCents,
            'currency' => $bill->currency ?: config('payments.currency'),
            'status' => 'pending',
            'method' => 'card',
            'livemode' => ! PaymentSettings::isTestMode(),
            'payer_name' => $payer['name'] ?? $bill->payer_name,
            'payer_email' => $payer['email'] ?? $bill->payer_email,
            'payer_phone' => $payer['phone'] ?? $bill->payer_phone,
            'ip' => $ip,
        ]);

        return self::createIntent($payment, $bill->reference . ': ' . ($bill->description ?: $bill->type?->label));
    }

    /**
     * Begin an open payment: a permit fee or similar, with no bill behind it.
     * This is the only path where the resident chooses the amount, so the
     * bounds are enforced again right here.
     */
    public static function startOpenPayment(BillType $type, int $amountCents, array $payer, ?string $ip = null): array
    {
        if (! $type->is_active || ! $type->allows_open_payment) {
            return self::fail('That payment type is not available online.');
        }

        if ($amountCents < $type->minCents() || $amountCents > $type->maxCents()) {
            return self::fail('Please enter an amount between '
                . \App\Support\Money::format($type->minCents()) . ' and '
                . \App\Support\Money::format($type->maxCents()) . '.');
        }

        $payment = Payment::create([
            'bill_id' => null,
            'bill_type_id' => $type->id,
            'amount_cents' => $amountCents,
            'currency' => config('payments.currency'),
            'status' => 'pending',
            'method' => 'card',
            'livemode' => ! PaymentSettings::isTestMode(),
            'payer_name' => $payer['name'] ?? null,
            'payer_email' => $payer['email'] ?? null,
            'payer_phone' => $payer['phone'] ?? null,
            'notes' => $payer['memo'] ?? null,
            'ip' => $ip,
        ]);

        return self::createIntent($payment, $type->label . ($payer['memo'] ?? '' ? ': ' . $payer['memo'] : ''));
    }

    /** An unfinished, still-valid payment for this bill and amount, if any. */
    private static function reusablePayment(?int $billId, int $amountCents): ?Payment
    {
        if (! $billId) {
            return null;
        }

        return Payment::where('bill_id', $billId)
            ->where('status', 'pending')
            ->where('amount_cents', $amountCents)
            ->whereNotNull('stripe_payment_intent_id')
            ->where('created_at', '>=', now()->subMinutes(self::REUSE_MINUTES))
            ->latest()
            ->first();
    }

    /** Hand back the client secret of an intent that is still usable. */
    private static function resume(Payment $payment): array
    {
        $result = StripeGateway::retrievePaymentIntent($payment->stripe_payment_intent_id);

        if (! $result['ok']) {
            return self::fail(self::residentMessage($result));
        }

        $intent = $result['data'];
        $status = $intent['status'] ?? '';

        // Already paid while the resident was away: converge rather than
        // offering to charge them again.
        if ($status === 'succeeded') {
            self::markSucceeded($payment, $intent);

            return ['ok' => true, 'payment' => $payment->fresh(), 'client_secret' => null, 'error' => null];
        }

        if (in_array($status, ['requires_payment_method', 'requires_confirmation', 'requires_action'], true)) {
            return [
                'ok' => true,
                'payment' => $payment,
                'client_secret' => $intent['client_secret'] ?? null,
                'error' => null,
            ];
        }

        // Canceled or otherwise spent: retire it and let the caller start over.
        $payment->forceFill(['status' => 'canceled'])->save();

        return self::fail('That payment session has expired. Please start again.');
    }

    private static function createIntent(Payment $payment, string $description): array
    {
        $params = [
            'amount' => $payment->amount_cents,
            'currency' => $payment->currency,
            'description' => Str::limit($description, 200, ''),
            'automatic_payment_methods' => ['enabled' => 'true'],
            // Our own reference travels with the charge so the municipality can
            // tie a Stripe dashboard row back to a bill without asking us.
            'metadata' => array_filter([
                'payment_reference' => $payment->reference,
                'bill_reference' => $payment->bill?->reference,
                'bill_id' => (string) ($payment->bill_id ?? ''),
                'source' => 'MunicipalMGR',
            ]),
        ];

        if ($descriptor = PaymentSettings::statementDescriptor()) {
            $params['statement_descriptor_suffix'] = $descriptor;
        }
        if ($payment->payer_email) {
            $params['receipt_email'] = $payment->payer_email;
        }

        $result = StripeGateway::createPaymentIntent($params, $payment->idempotency_key);

        if (! $result['ok']) {
            $payment->forceFill([
                'status' => 'failed',
                'failure_reason' => self::redact($result['error']),
            ])->save();

            return self::fail(self::residentMessage($result));
        }

        $intent = $result['data'];

        $payment->forceFill([
            'stripe_payment_intent_id' => $intent['id'] ?? null,
            'stripe_account_id' => PaymentSettings::connectAccountId(),
            'livemode' => (bool) ($intent['livemode'] ?? false),
        ])->save();

        return [
            'ok' => true,
            'payment' => $payment,
            'client_secret' => $intent['client_secret'] ?? null,
            'error' => null,
        ];
    }

    /**
     * The client secret for a pending payment's card form.
     *
     * Fetched fresh from Stripe on each render rather than parked in the
     * session, so a refreshed checkout page cannot resurrect a secret for an
     * intent that has since been paid or canceled.
     *
     * @return array{ok: bool, client_secret: ?string, settled: bool, error: ?string}
     */
    public static function clientSecret(Payment $payment): array
    {
        if ($payment->isSettled()) {
            return ['ok' => true, 'client_secret' => null, 'settled' => true, 'error' => null];
        }

        if (! $payment->stripe_payment_intent_id) {
            return ['ok' => false, 'client_secret' => null, 'settled' => false, 'error' => 'That payment session is no longer valid.'];
        }

        $result = StripeGateway::retrievePaymentIntent($payment->stripe_payment_intent_id);

        if (! $result['ok']) {
            return ['ok' => false, 'client_secret' => null, 'settled' => false, 'error' => self::residentMessage($result)];
        }

        $intent = $result['data'];

        // Paid while the page was open (or the webhook already landed): settle
        // now so the resident is sent to their receipt, not asked to pay again.
        if (($intent['status'] ?? '') === 'succeeded') {
            self::markSucceeded($payment, $intent);

            return ['ok' => true, 'client_secret' => null, 'settled' => true, 'error' => null];
        }

        if (! in_array($intent['status'] ?? '', ['requires_payment_method', 'requires_confirmation', 'requires_action'], true)) {
            return ['ok' => false, 'client_secret' => null, 'settled' => false, 'error' => 'That payment session has expired. Please start again.'];
        }

        return ['ok' => true, 'client_secret' => $intent['client_secret'] ?? null, 'settled' => false, 'error' => null];
    }

    /*
    |--------------------------------------------------------------------------
    | Settling a payment
    |--------------------------------------------------------------------------
    */

    /**
     * Pull the authoritative state from Stripe and apply it.
     *
     * Used by the browser return handler. The webhook may well have got there
     * first; both paths funnel into the same idempotent appliers, so whichever
     * arrives second is a no-op.
     */
    public static function syncFromStripe(Payment $payment): Payment
    {
        if (! $payment->stripe_payment_intent_id || $payment->isSettled()) {
            return $payment;
        }

        // expand latest_charge: card brand/last4 and the balance transaction
        // live on the charge, not the intent.
        $result = StripeGateway::request(
            'GET',
            '/v1/payment_intents/' . urlencode($payment->stripe_payment_intent_id),
            ['expand' => ['latest_charge']]
        );

        if (! $result['ok']) {
            return $payment;
        }

        return self::applyIntent($payment, $result['data']);
    }

    /** Route an intent object to the right terminal handler. */
    public static function applyIntent(Payment $payment, array $intent): Payment
    {
        return match ($intent['status'] ?? '') {
            'succeeded' => self::markSucceeded($payment, $intent),
            'canceled' => self::markCanceled($payment),
            'requires_payment_method' => self::markFailed(
                $payment,
                data_get($intent, 'last_payment_error.message', 'The card was declined.')
            ),
            default => $payment,
        };
    }

    /**
     * Apply a successful charge. Safe to call repeatedly and concurrently.
     *
     * The row lock plus the status check is the double-charge guard: if the
     * webhook and the redirect land at the same instant, one of them waits and
     * then finds the work already done.
     */
    public static function markSucceeded(Payment $payment, array $intent = []): Payment
    {
        DB::transaction(function () use ($payment, $intent) {
            /** @var Payment $locked */
            $locked = Payment::whereKey($payment->getKey())->lockForUpdate()->first();

            if (! $locked || $locked->isSettled()) {
                return; // Already applied by the other path.
            }

            $charge = is_array($intent['latest_charge'] ?? null) ? $intent['latest_charge'] : [];
            $card = data_get($charge, 'payment_method_details.card', []);

            $locked->forceFill(array_filter([
                'status' => 'succeeded',
                'paid_at' => now(),
                'stripe_charge_id' => $charge['id'] ?? null,
                'card_brand' => $card['brand'] ?? null,
                'card_last4' => $card['last4'] ?? null,
                'livemode' => $intent['livemode'] ?? $locked->livemode,
                'failure_reason' => null,
            ], fn ($v) => $v !== null))->save();

            // The bill total is recomputed from the ledger, never incremented.
            $locked->bill?->recalculate();

            $payment->setRawAttributes($locked->getAttributes(), true);
        });

        $fresh = $payment->fresh();

        // Everything past this point is best-effort. A resident's payment has
        // already succeeded; a mail failure or a CRM hiccup must never surface
        // as an error on their receipt page.
        rescue(fn () => self::linkConstituent($fresh), null, false);
        rescue(fn () => self::sendReceipt($fresh), null, false);

        return $fresh;
    }

    public static function markFailed(Payment $payment, ?string $reason = null): Payment
    {
        if ($payment->isSettled()) {
            return $payment; // A late failure event must not undo a success.
        }

        $payment->forceFill([
            'status' => 'failed',
            'failure_reason' => self::redact($reason ?: 'The payment did not complete.'),
        ])->save();

        return $payment;
    }

    public static function markCanceled(Payment $payment): Payment
    {
        if ($payment->isSettled()) {
            return $payment;
        }

        $payment->forceFill(['status' => 'canceled'])->save();

        return $payment;
    }

    /*
    |--------------------------------------------------------------------------
    | Refunds and offline payments (staff actions)
    |--------------------------------------------------------------------------
    */

    /**
     * Refund a card payment, fully or partially.
     * The amount is bounded by what is actually refundable, computed here.
     */
    public static function refund(Payment $payment, ?int $amountCents, ?string $reason = null): array
    {
        if (! $payment->isRefundable()) {
            return self::fail('That payment cannot be refunded.');
        }

        $refundable = $payment->refundableCents();
        $amountCents = $amountCents === null ? $refundable : min($amountCents, $refundable);

        if ($amountCents < 1) {
            return self::fail('Enter a refund amount greater than zero.');
        }

        // Distinct key per refund attempt so a partial refund followed by
        // another partial refund is not collapsed into one by Stripe.
        $idempotencyKey = 'refund-' . $payment->id . '-' . $payment->refunded_cents . '-' . $amountCents;

        $result = StripeGateway::createRefund($payment->stripe_payment_intent_id, $amountCents, $idempotencyKey, $reason);

        if (! $result['ok']) {
            return self::fail($result['error']);
        }

        DB::transaction(function () use ($payment, $amountCents) {
            /** @var Payment $locked */
            $locked = Payment::whereKey($payment->getKey())->lockForUpdate()->first();
            $refunded = $locked->refunded_cents + $amountCents;

            $locked->forceFill([
                'refunded_cents' => $refunded,
                'status' => $refunded >= $locked->amount_cents ? 'refunded' : 'partially_refunded',
                'refunded_at' => now(),
            ])->save();

            $locked->bill?->recalculate();
        });

        return ['ok' => true, 'payment' => $payment->fresh(), 'client_secret' => null, 'error' => null];
    }

    /**
     * Record a payment taken at the counter or received in the mail.
     * No Stripe involvement: this is bookkeeping, and it is audited by the
     * controller that calls it.
     */
    public static function recordOffline(Bill $bill, int $amountCents, string $method, User $staff, ?string $notes = null): Payment
    {
        $amountCents = min(max(1, $amountCents), $bill->balanceCents());

        $payment = Payment::create([
            'bill_id' => $bill->id,
            'bill_type_id' => $bill->bill_type_id,
            'constituent_id' => $bill->constituent_id,
            'amount_cents' => $amountCents,
            'currency' => $bill->currency,
            'status' => 'succeeded',
            'method' => $method,
            'livemode' => true, // Cash is always real money.
            'payer_name' => $bill->payer_name,
            'payer_email' => $bill->payer_email,
            'notes' => $notes,
            'recorded_by' => $staff->id,
            'paid_at' => now(),
        ]);

        $bill->recalculate();

        return $payment;
    }

    /*
    |--------------------------------------------------------------------------
    | Follow-up
    |--------------------------------------------------------------------------
    */

    /** Attach the payment to a resident record so it lands on their timeline. */
    private static function linkConstituent(?Payment $payment): void
    {
        if (! $payment || $payment->constituent_id) {
            return;
        }

        $constituent = Constituent::resolve([
            'name' => $payment->payer_name,
            'email' => $payment->payer_email,
            'phone' => $payment->payer_phone,
        ], 'payment');

        if (! $constituent) {
            return;
        }

        $payment->forceFill(['constituent_id' => $constituent->id])->save();

        if ($payment->bill && ! $payment->bill->constituent_id) {
            $payment->bill->forceFill(['constituent_id' => $constituent->id])->save();
        }

        $constituent->touchInteraction($payment->paid_at);
    }

    private static function sendReceipt(?Payment $payment): void
    {
        if (! $payment || ! $payment->payer_email || ! PaymentSettings::emailReceipts()) {
            return;
        }

        try {
            Mail::to($payment->payer_email)->send(new PaymentReceipt($payment));
        } catch (\Throwable $e) {
            // Log that it failed, not who it was for.
            Log::warning('Payment receipt email failed', [
                'payment_reference' => $payment->reference,
                'exception' => class_basename($e),
            ]);
        }
    }

    /**
     * Turn a gateway failure into something safe to show a resident.
     *
     * A card decline is worth repeating verbatim: "your card was declined" is
     * exactly what the payer needs to hear. A configuration failure is not.
     * "Invalid API Key provided" tells the resident nothing they can act on,
     * reads as though the site is broken in a way that is their problem, and
     * echoes back a string shaped like a credential. Those become a generic
     * message here; the real reason is kept on the payment record for staff and
     * in the application log.
     */
    private static function residentMessage(array $result): string
    {
        $cardProblems = [
            'card_declined', 'expired_card', 'incorrect_cvc', 'incorrect_number',
            'insufficient_funds', 'invalid_expiry_month', 'invalid_expiry_year',
            'processing_error', 'card_error',
        ];

        if (in_array((string) $result['code'], $cardProblems, true)) {
            return self::redact((string) $result['error']);
        }

        Log::warning('Payment could not be started', [
            'code' => $result['code'],
            'status' => $result['status'],
        ]);

        return 'We could not take that payment right now. Please try again in a few minutes, '
            . 'or pay at the town offices. Your card has not been charged.';
    }

    /**
     * Strip anything credential-shaped out of a message before it is stored or
     * displayed. Stripe already redacts its own keys, but a message that
     * reaches a database column and a web page should not depend on an upstream
     * service continuing to be careful on our behalf.
     */
    private static function redact(?string $message): string
    {
        $message = (string) $message;

        $message = preg_replace('/\b(sk|pk|rk)_(test|live)_[A-Za-z0-9*_]+/', '[redacted key]', $message);
        $message = preg_replace('/\bwhsec_[A-Za-z0-9*_]+/', '[redacted secret]', $message);

        return Str::limit($message, 250, '');
    }

    private static function fail(?string $message): array
    {
        return [
            'ok' => false,
            'payment' => null,
            'client_secret' => null,
            'error' => $message ?: 'Something went wrong. Please try again.',
        ];
    }
}
