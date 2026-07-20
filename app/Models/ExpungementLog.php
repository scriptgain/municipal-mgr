<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Proof that a court-ordered removal was carried out.
 *
 * Deliberately holds no subject name. A municipality needs to be able to show
 * an order was executed, on what date, by whom, against which case; it does
 * not need to keep the name it was ordered to erase.
 */
class ExpungementLog extends Model
{
    protected $fillable = [
        'case_number', 'booking_number', 'order_reference', 'ordered_by', 'reason',
        'performed_by', 'performed_by_name', 'performed_at', 'ip', 'mugshot_destroyed',
    ];

    protected function casts(): array
    {
        return ['performed_at' => 'datetime', 'mugshot_destroyed' => 'bool'];
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
