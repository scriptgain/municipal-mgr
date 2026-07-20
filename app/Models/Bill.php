<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A bill issued to a resident.
 *
 * The balance a resident is shown and the amount actually charged both come
 * from balanceCents() here. No caller anywhere is allowed to work out an amount
 * from a form field: that is the one place a payments module gets robbed.
 */
class Bill extends Model
{
    use Auditable;

    protected $fillable = [
        'reference', 'bill_type_id', 'constituent_id', 'account_number',
        'payer_name', 'payer_email', 'payer_phone',
        'lookup_surname', 'lookup_postal_code',
        'amount_cents', 'amount_paid_cents', 'currency',
        'description', 'notes', 'issued_on', 'due_date', 'status', 'paid_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'int',
            'amount_paid_cents' => 'int',
            'issued_on' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $bill) {
            $bill->reference ??= self::nextReference();
            $bill->currency ??= config('payments.currency', 'usd');
        });

        // Normalise the second factor on the way in, every time, from one
        // place. If the admin form and the importer normalised separately they
        // would drift, and a resident would be told their own bill is not theirs.
        static::saving(function (self $bill) {
            $bill->lookup_surname = self::surnameKey($bill->lookup_surname ?: self::surnameFrom($bill->payer_name));
            $bill->lookup_postal_code = self::postalKey($bill->lookup_postal_code);
        });
    }

    protected static function auditLabel(Model $m): string
    {
        return 'Bill "' . $m->reference . '"';
    }

    /** Human-quotable reference: BILL-2026-000412. */
    public static function nextReference(): string
    {
        $year = now()->format('Y');
        $n = static::where('reference', 'like', "BILL-{$year}-%")->count() + 1;

        // Collision guard: count() is not safe against concurrent inserts or a
        // deleted row, and the column is UNIQUE, so step past anything taken.
        do {
            $reference = sprintf('BILL-%s-%06d', $year, $n);
            $n++;
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    /*
    |--------------------------------------------------------------------------
    | Second factor
    |--------------------------------------------------------------------------
    | A bill reference on its own must never pull up a bill: references are
    | sequential and therefore guessable. The resident also supplies a surname
    | or a billing ZIP, compared against these normalised keys.
    */

    public static function surnameKey(?string $surname): ?string
    {
        $clean = preg_replace('/[^a-z]/', '', Str::lower(trim((string) $surname)));

        return $clean === '' ? null : $clean;
    }

    public static function postalKey(?string $postal): ?string
    {
        $clean = preg_replace('/[^0-9]/', '', (string) $postal);

        return $clean === '' ? null : substr($clean, 0, 5);
    }

    /** Best-effort surname from a full name, for bills imported without one. */
    public static function surnameFrom(?string $name): ?string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];

        return count($parts) ? end($parts) : null;
    }

    /**
     * Does this second factor match the bill?
     *
     * Accepts either the surname or the ZIP, whichever the resident has to
     * hand. hash_equals() keeps the comparison constant-time, so the endpoint
     * cannot be turned into a character-by-character oracle.
     */
    public function matchesSecondFactor(?string $supplied): bool
    {
        $supplied = trim((string) $supplied);
        if ($supplied === '') {
            return false;
        }

        $candidates = array_filter([$this->lookup_surname, $this->lookup_postal_code]);
        $supplied = [self::surnameKey($supplied), self::postalKey($supplied)];

        foreach ($candidates as $stored) {
            foreach ($supplied as $given) {
                if ($given !== null && hash_equals((string) $stored, (string) $given)) {
                    return true;
                }
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Money
    |--------------------------------------------------------------------------
    */

    /** What is still owed, in cents. Never negative. */
    public function balanceCents(): int
    {
        return max(0, $this->amount_cents - $this->amount_paid_cents);
    }

    public function amountFormatted(): string
    {
        return Money::format($this->amount_cents);
    }

    public function paidFormatted(): string
    {
        return Money::format($this->amount_paid_cents);
    }

    public function balanceFormatted(): string
    {
        return Money::format($this->balanceCents());
    }

    /**
     * Recalculate paid total and status from the payments actually recorded,
     * then persist. Called after every payment or refund settles, so the bill
     * is derived from the ledger rather than incremented and hoped for.
     */
    public function recalculate(): void
    {
        if ($this->status === 'void') {
            return;
        }

        $paid = (int) $this->payments()
            ->whereIn('status', ['succeeded', 'partially_refunded'])
            ->sum('amount_cents');

        $refunded = (int) $this->payments()->sum('refunded_cents');
        $paid = max(0, $paid - $refunded);

        $status = match (true) {
            $paid >= $this->amount_cents && $this->amount_cents > 0 => 'paid',
            $paid > 0 => 'partially_paid',
            default => 'unpaid',
        };

        $this->forceFill([
            'amount_paid_cents' => $paid,
            'status' => $status,
            'paid_at' => $status === 'paid' ? ($this->paid_at ?? now()) : null,
        ])->save();
    }

    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    public function isPayable(): bool
    {
        return in_array($this->status, ['unpaid', 'partially_paid'], true) && $this->balanceCents() > 0;
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null && $this->isPayable() && $this->due_date->isPast();
    }

    public function statusLabel(): string
    {
        return config('payments.bill_statuses.' . $this->status . '.label', Str::headline($this->status));
    }

    public function statusColor(): string
    {
        // An overdue bill reads as a problem even though its status is "unpaid".
        if ($this->isOverdue()) {
            return 'danger';
        }

        return config('payments.bill_statuses.' . $this->status . '.color', 'neutral');
    }

    /** Plain-language line for the resident's review screen. */
    public function dueLabel(): string
    {
        if (! $this->due_date) {
            return 'No Due Date';
        }
        if ($this->isOverdue()) {
            return 'Overdue Since ' . $this->due_date->format(config('municipal.date_format'));
        }

        return 'Due ' . $this->due_date->format(config('municipal.date_format'));
    }

    /*
    |--------------------------------------------------------------------------
    | Relations and queries
    |--------------------------------------------------------------------------
    */

    public function type(): BelongsTo
    {
        return $this->belongsTo(BillType::class, 'bill_type_id');
    }

    public function constituent(): BelongsTo
    {
        return $this->belongsTo(Constituent::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
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
            ->orWhere('account_number', 'like', $like)
            ->orWhere('payer_name', 'like', $like)
            ->orWhere('payer_email', 'like', $like)
            ->orWhere('description', 'like', $like));
    }

    public function scopeOutstanding(Builder $q): Builder
    {
        return $q->whereIn('status', ['unpaid', 'partially_paid']);
    }

    public function scopeOverdue(Builder $q): Builder
    {
        return $q->outstanding()->whereNotNull('due_date')->whereDate('due_date', '<', now());
    }
}
