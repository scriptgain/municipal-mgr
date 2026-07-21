<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A dated release note for the public Changelog / What's New page.
 *
 * Visibility here is a single is_published flag rather than the shared
 * Publishable status/published_at pair: a release note is either announced or
 * it is not, and it has no future-dated embargo the way news does. The public
 * page must only ever read through published().
 */
class ChangelogEntry extends Model
{
    use Auditable;

    protected $fillable = [
        'version', 'released_on', 'title', 'summary', 'body', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'released_on' => 'date',
            'is_published' => 'bool',
        ];
    }

    /** Public visibility. The public page reads only through this scope. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** Newest release first: by date, then id for entries sharing a date. */
    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('released_on')->orderByDesc('id');
    }

    public function isPublished(): bool
    {
        return (bool) $this->is_published;
    }

    /**
     * The Markdown body rendered to safe HTML.
     *
     * Uses Str::markdown(), which wraps league/commonmark (already in the lock)
     * — rendered here in the model, never with logic in the view. Empty bodies
     * render to an empty string so the template can guard on it cleanly.
     */
    public function renderedBody(): string
    {
        $body = trim((string) $this->body);

        return $body === '' ? '' : Str::markdown($body);
    }
}
