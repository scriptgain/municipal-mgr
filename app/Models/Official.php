<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Official extends Model
{
    use Auditable;

    protected $fillable = [
        'name', 'office', 'district', 'email', 'phone', 'bio', 'photo_path',
        'term_start', 'term_end', 'sort_order', 'is_current', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'term_start' => 'date',
            'term_end' => 'date',
            'is_current' => 'bool',
            'is_published' => 'bool',
            'sort_order' => 'int',
        ];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function scopeCurrent(Builder $q): Builder
    {
        return $q->where('is_current', true);
    }

    public function initials(): string
    {
        return Str::of($this->name)->explode(' ')->filter()->take(2)
            ->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)))->implode('');
    }

    /** "Jan 2025 — Dec 2028", or "Term Ends Dec 2028" when the start is unknown. */
    public function termDisplay(): ?string
    {
        if ($this->term_start && $this->term_end) {
            return $this->term_start->format('M Y') . ' to ' . $this->term_end->format('M Y');
        }

        return $this->term_end ? 'Term Ends ' . $this->term_end->format('M Y') : null;
    }
}
