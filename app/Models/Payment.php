<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A payment, online (Stripe) or offline (counter, mail).
 *
 * Holds no card data. `card_brand` and `card_last4` are display strings for the
 * receipt and cannot be transacted with; there is no PAN, no CVC and no raw
 * token anywhere in this model or its table.
 *
 * NOT Auditable: the trait fires on every create, and a resident paying their
 * water bill at 11pm is not a staff action. Staff actions against payments
 * (offline entry, refund, void) are audited explicitly in the controllers.
 */
class Payment extends Model
{
    protected $fillable = [
        'reference', 'bill_id', 'bill_type_id', 'constituent_id',
        'amount_cents', 'refunded_cents', 'currency', 'status', 'method',
        'stripe_payment_intent_id', 'stripe_charge_id', 'stripe_payout_id',
        'stripe_account_id', 'livemode', 'card_brand', 'card_last4',
        'payer_name', 'payer_email', 'payer_phone',
        'idempotency_key', 'receipt_token', 'failure_reason', 'notes',
        'recorded_by', 'paid_at', 'refunded_at', 'payout_arrival_at', 'ip',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'int',
            'refunded_cents' => 'int',
            'livemode' => 'bool',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'payout_arrival_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $payment) {
            $payment->reference ??= self::nextReference();
            $payment->receipt_token ??= Str::lower(Str::random(48));
            $payment->idempotency_key ??= (string) Str::uuid();
            $payment->currency ??= config('payments.currency', 'usd');
        });
    }

    /** Human-quotable payment reference: PAY-2026-000412. */
    public static function nextReference(): string
    {
        $year = now()->format('Y');
        $n = static::where('reference', 'like', "PAY-{$year}-%")->count() + 1;

        do {
            $reference = sprintf('PAY-%s-%06d', $year, $n);
            $n++;
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    /*
    |--------------------------------------------------------------------------
    | Money and state
    |--------------------------------------------------------------------------
    */

    public function amountFormatted(): string
    {
        return Money::format($this->amount_cents);
    }

    public function refundedFormatted(): string
    {
        return Money::format($this->refunded_cents);
    }

    /** What is still refundable, in cents. */
    public function refundableCents(): int
    {
        if (! in_array($this->status, ['succeeded', 'partially_refunded'], true)) {
            return 0;
        }

        return max(0, $this->amount_cents - $this->refunded_cents);
    }

    public function isRefundable(): bool
    {
        // Offline payments have no Stripe charge to reverse; staff refund those
        // at the counter and mark them here, which is a different action.
        return $this->method === 'card'
            && $this->stripe_payment_intent_id !== null
            && $this->refundableCents() > 0;
    }

    public function isSettled(): bool
    {
        return in_array($this->status, ['succeeded', 'partially_refunded', 'refunded'], true);
    }

    public function statusLabel(): string
    {
        return config('payments.payment_statuses.' . $this->status . '.label', Str::headline($this->status));
    }

    public function statusColor(): string
    {
        return config('payments.payment_statuses.' . $this->status . '.color', 'neutral');
    }

    public function methodLabel(): string
    {
        return config('payments.methods.' . $this->method, Str::headline($this->method));
    }

    /** "Visa ending 4242", or the method name when it was not a card. */
    public function instrumentLabel(): string
    {
        if ($this->card_brand && $this->card_last4) {
            return Str::title($this->card_brand) . ' Ending ' . $this->card_last4;
        }

        return $this->methodLabel();
    }

    /** Test-mode payments must be unmistakable wherever they are displayed. */
    public function isTestPayment(): bool
    {
        return $this->method === 'card' && ! $this->livemode;
    }

    /*
    |--------------------------------------------------------------------------
    | Relations and queries
    |--------------------------------------------------------------------------
    */

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(BillType::class, 'bill_type_id');
    }

    public function constituent(): BelongsTo
    {
        return $this->belongsTo(Constituent::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeSettled(Builder $q): Builder
    {
        return $q->whereIn('status', ['succeeded', 'partially_refunded', 'refunded']);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

        return $q->where(fn (Builder $s) => $s
            ->where('reference', 'like', $like)
            ->orWhere('payer_name', 'like', $like)
            ->orWhere('payer_email', 'like', $like)
            ->orWhere('stripe_payment_intent_id', 'like', $like)
            ->orWhereHas('bill', fn (Builder $b) => $b->where('reference', 'like', $like)));
    }
}
