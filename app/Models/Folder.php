<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A folder in the unified file manager.
 *
 * Replaces the old flat DocumentCategory list: folders nest via parent_id, so
 * a clerk can file "Ordinances / 2026" rather than inventing a flat category
 * per year. Depth is capped by MAX_DEPTH because a municipal file tree that
 * runs deeper than a handful of levels stops being navigable for residents.
 */
class Folder extends Model
{
    use Auditable, HasSlug;

    /** Deepest nesting the UI offers. Guards against accidental cycles too. */
    public const MAX_DEPTH = 5;

    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'icon', 'sort_order', 'is_public',
    ];

    protected function casts(): array
    {
        return ['sort_order' => 'int', 'is_public' => 'bool'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function files(): HasMany
    {
        return $this->hasMany(FileItem::class);
    }

    public function scopeRoots(Builder $q): Builder
    {
        return $q->whereNull('parent_id');
    }

    /** Folders residents may see in the public browser. */
    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('is_public', true);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Root-down trail including this folder, used for breadcrumbs.
     * Bounded by MAX_DEPTH so a corrupt parent chain cannot spin forever.
     */
    public function trail(): array
    {
        $trail = [$this];
        $node = $this;
        $guard = 0;
        while ($node->parent && $guard++ < self::MAX_DEPTH + 1) {
            $node = $node->parent;
            array_unshift($trail, $node);
        }

        return $trail;
    }

    public function depth(): int
    {
        return count($this->trail()) - 1;
    }

    /** Ids of this folder and every folder beneath it. */
    public function descendantIds(?Collection $all = null): array
    {
        $all ??= static::query()->get(['id', 'parent_id']);
        $ids = [$this->id];
        $frontier = [$this->id];
        $guard = 0;

        while ($frontier && $guard++ < self::MAX_DEPTH + 1) {
            $frontier = $all->whereIn('parent_id', $frontier)->pluck('id')->all();
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    /**
     * Flattened tree as [folder, depth] pairs, for select menus and the
     * sidebar tree. Built in PHP from one query rather than a recursive
     * relation load, which on a few hundred folders is a lot of round trips.
     */
    public static function tree(?Collection $all = null): array
    {
        $all ??= static::query()->ordered()->get();
        $byParent = $all->groupBy(fn ($f) => $f->parent_id ?? 0);
        $out = [];

        $walk = function ($parentId, int $depth) use (&$walk, $byParent, &$out) {
            foreach ($byParent->get($parentId, collect()) as $folder) {
                // `indent` and `prefix` are precomputed here so the templates
                // stay pure markup: no arithmetic in an attribute, no
                // str_repeat inside a <option>.
                $out[] = [
                    'folder' => $folder,
                    'depth' => $depth,
                    'indent' => $depth * 16,
                    'prefix' => str_repeat('- ', $depth),
                ];
                if ($depth < self::MAX_DEPTH) {
                    $walk($folder->id, $depth + 1);
                }
            }
        };
        $walk(0, 0);

        return $out;
    }
}
