<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NewsPost extends Model
{
    use Auditable, HasSlug, Publishable;

    protected $fillable = [
        'department_id', 'title', 'slug', 'category', 'excerpt', 'body',
        'image_path', 'is_featured', 'status', 'published_at', 'author_id',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'is_featured' => 'bool'];
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
}
