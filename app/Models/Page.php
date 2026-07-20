<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use Auditable, HasSlug, Publishable;

    protected $fillable = [
        'parent_id', 'department_id', 'title', 'slug', 'summary', 'hero_image_path',
        'sections', 'template', 'status', 'published_at', 'updated_by',
        'meta_description', 'sort_order', 'show_in_nav',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'published_at' => 'datetime',
            'show_in_nav' => 'bool',
            'sort_order' => 'int',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Section blocks, normalised so a view never has to defend against nulls. */
    public function blocks(): array
    {
        return collect($this->sections ?? [])
            ->filter(fn ($b) => is_array($b) && ! empty($b['type']))
            ->map(fn ($b) => $b + ['heading' => null, 'body' => null, 'items' => []])
            ->values()->all();
    }

    /** Breadcrumb trail from the root down to this page. */
    public function trail(): array
    {
        $trail = [$this];
        $node = $this;
        $guard = 0;
        while ($node->parent && $guard++ < 6) {
            $node = $node->parent;
            array_unshift($trail, $node);
        }

        return $trail;
    }
}
