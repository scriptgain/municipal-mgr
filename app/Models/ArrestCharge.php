<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single charge on a booking. A booking is rarely one count, and collapsing
 * several charges into a free-text blob makes the disposition meaningless.
 */
class ArrestCharge extends Model
{
    protected $fillable = [
        'arrest_record_id', 'description', 'statute', 'severity', 'counts', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['counts' => 'int', 'sort_order' => 'int'];
    }

    public function arrestRecord(): BelongsTo
    {
        return $this->belongsTo(ArrestRecord::class);
    }

    public function severityLabel(): string
    {
        return config("records.charge_severities.{$this->severity}.label", 'Other');
    }

    public function severityColor(): string
    {
        return config("records.charge_severities.{$this->severity}.color", 'neutral');
    }
}
