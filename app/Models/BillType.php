<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A kind of thing the town bills for. Staff-configurable so a municipality can
 * add "Cemetery Plot Maintenance" without a software release.
 */
class BillType extends Model
{
    use Auditable;

    protected $fillable = [
        'key', 'label', 'description', 'icon', 'requires_lookup', 'allows_open_payment',
        'min_amount_cents', 'max_amount_cents', 'department_id', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_lookup' => 'bool',
            'allows_open_payment' => 'bool',
            'is_active' => 'bool',
            'min_amount_cents' => 'int',
            'max_amount_cents' => 'int',
            'sort_order' => 'int',
        ];
    }

    /** The audit trail should name the type, not its numeric key. */
    protected static function auditLabel(Model $m): string
    {
        return 'Bill Type "' . $m->label . '"';
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('label');
    }

    /** Types a resident may pay against without a bill reference. */
    public function scopeOpenPayable(Builder $q): Builder
    {
        return $q->where('is_active', true)->where('allows_open_payment', true);
    }

    /**
     * Clamp bounds for the open-payment amount box.
     * Falls back to the module-wide guard rails when the type sets none.
     */
    public function minCents(): int
    {
        return $this->min_amount_cents ?: (int) config('payments.open_payment.min_cents', 100);
    }

    public function maxCents(): int
    {
        return $this->max_amount_cents ?: (int) config('payments.open_payment.max_cents', 2500000);
    }

    /*
    | Form values for the admin edit screen. Blank rather than zero when unset,
    | so an empty box means "use the module default" and does not read as a
    | deliberate $0.00 limit. Model accessors, so the Blade template stays
    | markup only.
    */

    public function minDecimal(): string
    {
        return $this->min_amount_cents ? \App\Support\Money::decimal($this->min_amount_cents) : '';
    }

    public function maxDecimal(): string
    {
        return $this->max_amount_cents ? \App\Support\Money::decimal($this->max_amount_cents) : '';
    }
}
