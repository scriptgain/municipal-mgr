<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shared visibility rules for public content. The PUBLIC site must only ever
 * read through published(); the admin panel reads unscoped.
 */
trait Publishable
{
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function scopeDrafts(Builder $query): Builder
    {
        return $query->where('status', '!=', 'published');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && ($this->published_at === null || $this->published_at->isPast());
    }
}
