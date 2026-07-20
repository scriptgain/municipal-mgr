<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bid extends Model
{
    use Auditable, HasSlug;

    protected $fillable = [
        'department_id', 'title', 'slug', 'reference', 'bid_type', 'description',
        'document_id', 'contact_name', 'contact_email', 'opens_at', 'closes_at',
        'pre_bid_meeting_at', 'status', 'awarded_to', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'pre_bid_meeting_at' => 'datetime',
            'is_published' => 'bool',
        ];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('status', 'open')
            ->where(fn (Builder $s) => $s->whereNull('closes_at')->orWhere('closes_at', '>=', now()));
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(FileItem::class, 'document_id');
    }

    public function isClosed(): bool
    {
        return $this->status !== 'open' || ($this->closes_at && $this->closes_at->isPast());
    }
}
