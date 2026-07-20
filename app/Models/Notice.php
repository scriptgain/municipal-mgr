<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notice extends Model
{
    use Auditable, HasSeo, HasSlug;

    protected $fillable = [
        'department_id', 'document_id', 'title', 'slug', 'notice_type', 'body',
        'posted_at', 'expires_at', 'status',
        'meta_title', 'meta_description', 'og_image', 'canonical_url', 'noindex',
    ];

    protected function casts(): array
    {
        return ['posted_at' => 'datetime', 'expires_at' => 'datetime', 'noindex' => 'bool'];
    }

    /** Currently posted: published, past its posting date, not yet expired. */
    public function scopeCurrent(Builder $q): Builder
    {
        return $q->where('status', 'published')
            ->where(fn (Builder $s) => $s->whereNull('posted_at')->orWhere('posted_at', '<=', now()))
            ->where(fn (Builder $s) => $s->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(FileItem::class, 'document_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /* ------------------------------------------------------------------ */
    /* SEO                                                                 */
    /* ------------------------------------------------------------------ */

    protected function seoRouteName(): ?string
    {
        return 'site.notices.show';
    }

    public function seoSchemaType(): ?string
    {
        return 'Article';
    }

    protected function seoDescriptionSources(): array
    {
        return ['body'];
    }

    /**
     * An expired notice stays indexable on purpose. A statutory posting is a
     * public record that people cite long after it comes down, and the page
     * still resolves, so removing it from search would hide a live document.
     */
    public function seoIsPublic(): bool
    {
        return ! $this->seoNoindex() && $this->status === 'published';
    }
}
