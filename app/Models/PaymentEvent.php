<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A Stripe webhook event we have seen.
 *
 * The unique `stripe_event_id` is the idempotency record: Stripe retries
 * deliveries on any non-2xx, and a retried event must not apply twice.
 *
 * `summary` is a short generated line, never the payload. Webhook payloads
 * contain cardholder names, billing addresses and email addresses, and none of
 * that belongs in a log table on a government system.
 */
class PaymentEvent extends Model
{
    protected $fillable = [
        'stripe_event_id', 'type', 'stripe_account_id', 'payment_id',
        'summary', 'handled', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'handled' => 'bool',
            'processed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
