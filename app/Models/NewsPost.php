<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NewsPost extends Model
{
    use Auditable, HasSeo, HasSlug, Publishable;

    protected $fillable = [
        'department_id', 'title', 'slug', 'category', 'excerpt', 'body',
        'image_path', 'is_featured', 'status', 'published_at', 'author_id',
        'meta_title', 'meta_description', 'og_image', 'canonical_url', 'noindex',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'is_featured' => 'bool', 'noindex' => 'bool'];
    }

    public function scopeLatestFirst(Builder $q): Builder
    {
        return $q->orderByDesc('published_at')->orderByDesc('id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Teaser text for cards — the stored excerpt, else a trimmed body. */
    public function teaser(int $chars = 180): string
    {
        return Str::limit(trim((string) ($this->excerpt ?: strip_tags((string) $this->body))), $chars);
    }

    /* ------------------------------------------------------------------ */
    /* SEO                                                                 */
    /* ------------------------------------------------------------------ */

    protected function seoRouteName(): ?string
    {
        return 'site.news.show';
    }

    public function seoSchemaType(): ?string
    {
        return 'NewsArticle';
    }

    protected function seoDescriptionSources(): array
    {
        return ['excerpt', 'body'];
    }

    protected function seoImageSources(): array
    {
        return ['og_image', 'image_path'];
    }
}
