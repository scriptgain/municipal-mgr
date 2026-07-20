<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use Auditable;

    protected $fillable = [
        'title', 'message', 'level', 'link_url', 'link_label',
        'starts_at', 'ends_at', 'is_active', 'is_dismissible',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'bool',
            'is_dismissible' => 'bool',
        ];
    }

    /** Live right now: active and inside its scheduling window. */
    public function scopeLive(Builder $q): Builder
    {
        return $q->where('is_active', true)
            ->where(fn (Builder $s) => $s->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $s) => $s->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /** Severity ordering so an emergency always outranks an advisory. */
    public function weight(): int
    {
        return ['emergency' => 4, 'warning' => 3, 'advisory' => 2, 'info' => 1][$this->level] ?? 1;
    }
}
