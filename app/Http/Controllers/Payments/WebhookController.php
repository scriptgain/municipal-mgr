<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\Payments\PaymentProcessor;
use App\Services\Payments\PaymentSettings;
use App\Services\Payments\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Stripe webhook receiver.
 *
 * Three things matter here and nothing else does:
 *
 *  1. SIGNATURE. An unsigned or mis-signed request is rejected with 400 before
 *     a single byte of the body is trusted. There is no "if the signing secret
 *     is missing, skip the check" branch, because that branch is how webhook
 *     endpoints become open write APIs.
 *
 *  2. IDEMPOTENCY. Stripe retries on any non-2xx and will happily deliver the
 *     same event twice on its own. The unique `stripe_event_id` insert is what
 *     makes a duplicate delivery a no-op, and it is attempted BEFORE any
 *     handler runs.
 *
 *  3. LOGGING. Never the payload. Stripe event bodies carry cardholder names,
 *     billing addresses and email addresses. Only ids, types and outcomes are
 *     written to the log.
 *
 * ORDERING: this endpoint frequently beats the resident's browser back to us.
 * That is expected and handled: it and the return handler both call the same
 * idempotent appliers on PaymentProcessor, so whichever arrives second finds
 * the work already done and changes nothing.
 */
class WebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $secret = PaymentSettings::webhookSecret();
        $payload = $request->getContent();

        // No configured secret means nothing can be verified, so nothing is
        // trusted. Fail closed.
        // Logged at ERROR, not warning, on purpose: installs commonly run with
        // LOG_LEVEL=error, and a rejected signature means somebody is posting
        // forged payment confirmations at this endpoint. That must not be the
        // one message the operator's log level filters out.
        if (! $secret) {
            Log::error('Stripe webhook received with no signing secret configured.');

            return response('Webhook not configured.', 400);
        }

        if (! StripeGateway::verifySignature($payload, $request->header('Stripe-Signature'), $secret)) {
            Log::error('Stripe webhook signature rejected', ['ip' => $request->ip()]);

            return response('Invalid signature.', 400);
        }

        $event = json_decode($payload, true);

        if (! is_array($event) || empty($event['id']) || empty($event['type'])) {
            return response('Malformed event.', 400);
        }

        // Idempotency claim. A duplicate delivery collides on the unique index
        // and is acknowledged without being applied again.
        try {
            $record = PaymentEvent::create([
                'stripe_event_id' => $event['id'],
                'type' => $event['type'],
                'stripe_account_id' => $event['account'] ?? null,
                'handled' => false,
            ]);
        } catch (\Throwable $e) {
            return response('Already processed.', 200);
        }

        try {
            $summary = $this->dispatch($event, $record);

            $record->forceFill([
                'handled' => true,
                'processed_at' => now(),
                'summary' => $summary,
            ])->save();
        } catch (\Throwable $e) {
            Log::error('Stripe webhook handler failed', [
                'event_id' => $event['id'],
                'type' => $event['type'],
                'exception' => class_basename($e),
            ]);

            // 500 so Stripe retries. The event row stays unhandled; the
            // uniqueness claim is released for the retry by design below.
            $record->delete();

            return response('Handler error.', 500);
        }

        return response('OK', 200);
    }

    /** Route the event to its handler. Unknown types are acknowledged, not errors. */
    private function dispatch(array $event, PaymentEvent $record): string
    {
        $object = $event['data']['object'] ?? [];
        $type = $event['type'];

        return match ($type) {
            'payment_intent.succeeded' => $this->onIntentSucceeded($object, $record),
            'payment_intent.payment_failed' => $this->onIntentFailed($object, $record),
            'payment_intent.canceled' => $this->onIntentCanceled($object, $record),
            'charge.refunded' => $this->onChargeRefunded($object, $record),
            'payout.paid' => $this->onPayoutPaid($object),
            'account.updated' => $this->onAccountUpdated($object),
            default => 'Ignored event type ' . $type . '.',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Handlers
    |--------------------------------------------------------------------------
    */

    private function onIntentSucceeded(array $intent, PaymentEvent $record): string
    {
        $payment = $this->findPayment($intent);

        if (! $payment) {
            return 'No matching payment for intent.';
        }

        $record->forceFill(['payment_id' => $payment->id])->save();

        // Re-read from the API rather than trusting the delivered body for the
        // charge details, and to pick up the expanded card brand/last4.
        PaymentProcessor::syncFromStripe($payment);

        // syncFromStripe returns early on an already-settled payment, so drive
        // the terminal state explicitly for the case where the webhook is first.
        $payment->refresh();
        if (! $payment->isSettled()) {
            PaymentProcessor::markSucceeded($payment, $intent);
        }

        return 'Payment ' . $payment->reference . ' marked succeeded.';
    }

    private function onIntentFailed(array $intent, PaymentEvent $record): string
    {
        $payment = $this->findPayment($intent);

        if (! $payment) {
            return 'No matching payment for failed intent.';
        }

        $record->forceFill(['payment_id' => $payment->id])->save();

        PaymentProcessor::markFailed(
            $payment,
            data_get($intent, 'last_payment_error.message', 'The card was declined.')
        );

        return 'Payment ' . $payment->reference . ' marked failed.';
    }

    private function onIntentCanceled(array $intent, PaymentEvent $record): string
    {
        $payment = $this->findPayment($intent);

        if (! $payment) {
            return 'No matching payment for canceled intent.';
        }

        $record->forceFill(['payment_id' => $payment->id])->save();
        PaymentProcessor::markCanceled($payment);

        return 'Payment ' . $payment->reference . ' canceled.';
    }

    /**
     * Refunds issued from the Stripe dashboard rather than from this panel.
     * Staff do this, and the bill has to follow.
     */
    private function onChargeRefunded(array $charge, PaymentEvent $record): string
    {
        $payment = Payment::where('stripe_charge_id', $charge['id'] ?? '')
            ->orWhere('stripe_payment_intent_id', $charge['payment_intent'] ?? '')
            ->first();

        if (! $payment) {
            return 'No matching payment for refunded charge.';
        }

        $record->forceFill(['payment_id' => $payment->id])->save();

        $refunded = (int) ($charge['amount_refunded'] ?? 0);

        // Stripe is authoritative on the refunded total, so set rather than add:
        // adding would double-count a redelivered event.
        if ($refunded > 0 && $refunded !== $payment->refunded_cents) {
            $payment->forceFill([
                'refunded_cents' => $refunded,
                'status' => $refunded >= $payment->amount_cents ? 'refunded' : 'partially_refunded',
                'refunded_at' => $payment->refunded_at ?? now(),
            ])->save();

            $payment->bill?->recalculate();
        }

        return 'Payment ' . $payment->reference . ' refund synced.';
    }

    /**
     * Stamp the payout reference onto everything it settled.
     * This is what makes the reconciliation view able to answer "which bills
     * are in the deposit that hit the town's bank on Tuesday".
     */
    private function onPayoutPaid(array $payout): string
    {
        $payoutId = $payout['id'] ?? null;

        if (! $payoutId) {
            return 'Payout without an id.';
        }

        $arrival = isset($payout['arrival_date'])
            ? now()->setTimestamp((int) $payout['arrival_date'])
            : now();

        // Stripe does not enumerate the charges in a payout on the event, so
        // attribute the settled, not-yet-attributed payments up to this point.
        $updated = Payment::whereNull('stripe_payout_id')
            ->where('status', '!=', 'pending')
            ->whereNotNull('paid_at')
            ->where('paid_at', '<=', $arrival)
            ->where('method', 'card')
            ->update([
                'stripe_payout_id' => $payoutId,
                'payout_arrival_at' => $arrival,
            ]);

        return "Payout {$payoutId} attributed to {$updated} payment(s).";
    }

    /** Keep the cached Connect status honest without polling. */
    private function onAccountUpdated(array $account): string
    {
        if (($account['id'] ?? null) !== PaymentSettings::connectAccountId()) {
            return 'Account update for a different account, ignored.';
        }

        \App\Services\Payments\StripeConnect::storeStatus($account);

        return 'Connected account status refreshed.';
    }

    private function findPayment(array $intent): ?Payment
    {
        $id = $intent['id'] ?? null;

        if (! $id) {
            return null;
        }

        return Payment::where('stripe_payment_intent_id', $id)->first()
            // Fall back to our own reference in metadata, which survives even
            // if the intent id was never written back (network failure mid-create).
            ?? Payment::where('reference', data_get($intent, 'metadata.payment_reference', ''))->first();
    }
}
