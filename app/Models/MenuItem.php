<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use Auditable;

    protected $fillable = [
        'menu', 'parent_id', 'page_id', 'label', 'url', 'icon',
        'description', 'new_tab', 'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return ['new_tab' => 'bool', 'is_published' => 'bool', 'sort_order' => 'int'];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function scopeMenu(Builder $q, string $menu): Builder
    {
        return $q->where('menu', $menu)->orderBy('sort_order')->orderBy('label');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /** Resolved href: an internal page wins over a manually typed URL. */
    public function href(): string
    {
        if ($this->page) {
            return route('site.page', $this->page->slug);
        }

        return (string) ($this->url ?: '#');
    }
}
