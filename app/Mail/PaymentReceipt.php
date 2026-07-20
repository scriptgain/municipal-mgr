<?php

namespace App\Mail;

use App\Models\Payment;
use App\Services\Payments\PaymentSettings;
use App\Services\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The receipt a resident gets by email.
 *
 * Carries the payment reference, the amount, what it was for, and a permanent
 * link back to the receipt page. It does NOT carry card details beyond the
 * brand and last four, and it never carries anything that would let the
 * recipient (or anyone who is forwarded the mail) reach another resident's
 * record: the link is scoped to this payment's own receipt token.
 */
class PaymentReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment)
    {
    }

    public function envelope(): Envelope
    {
        $name = SiteSettings::formalName();
        $prefix = $this->payment->isTestPayment() ? '[TEST] ' : '';

        return new Envelope(
            subject: $prefix . 'Payment Receipt ' . $this->payment->reference . ': ' . $name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-receipt',
            with: [
                'payment' => $this->payment,
                'bill' => $this->payment->bill,
                'siteName' => SiteSettings::formalName(),
                'receiptUrl' => route('site.pay.receipt', $this->payment->receipt_token),
                'supportEmail' => PaymentSettings::supportEmail(),
                'supportPhone' => PaymentSettings::supportPhone(),
                'isTestPayment' => $this->payment->isTestPayment(),
            ],
        );
    }
}
