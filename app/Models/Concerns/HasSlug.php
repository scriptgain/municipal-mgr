<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Slugs a model from its title/name on create, and guarantees uniqueness.
 * Slugs are NOT regenerated on update: a published municipal URL is often
 * printed on a mailer or cited in an ordinance, so silently moving it would
 * break links residents are told to use.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function (Model $m) {
            if (blank($m->slug)) {
                $m->slug = static::uniqueSlug($m->slugSource());
            }
        });
    }

    /** The attribute a slug is derived from. Override where it isn't a title. */
    protected function slugSource(): string
    {
        return (string) ($this->title ?? $this->name ?? Str::random(8));
    }

    public static function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: Str::lower(Str::random(8));
        $slug = $base;
        $n = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
