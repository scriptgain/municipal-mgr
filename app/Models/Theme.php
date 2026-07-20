<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * A named bag of design tokens.
 *
 * Presets ship with the product and are protected from edit and delete, so an
 * install always has a known-good look to fall back to.
 */
class Theme extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'tokens', 'is_active', 'is_preset', 'created_by'];

    protected $casts = [
        'tokens' => 'array',
        'is_active' => 'boolean',
        'is_preset' => 'boolean',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * One token, with the shipped default filled in.
     *
     * Lives on the model so views can ask for a colour without doing array
     * fallbacks in Blade, and so a partially-specified theme is always complete
     * wherever it is read.
     */
    public function token(string $key): string
    {
        $tokens = (array) ($this->tokens ?? []);
        $value = $tokens[$key] ?? null;

        return (string) ($value !== null && $value !== '' ? $value : config('themes.defaults.' . $key, ''));
    }

    /** URL that renders the live site with this theme, without activating it. */
    public function previewUrl(): string
    {
        return url('/?' . http_build_query([\App\Services\Themes\ThemeService::PREVIEW_PARAM => $this->id]));
    }

    public function adminPreviewUrl(): string
    {
        return route('dashboard') . '?' . http_build_query([\App\Services\Themes\ThemeService::PREVIEW_PARAM => $this->id]);
    }

    /** Any write to any theme invalidates the cached active theme. */
    protected static function booted(): void
    {
        $bust = fn () => Cache::forget(\App\Services\Themes\ThemeService::CACHE_KEY);
        static::saved($bust);
        static::deleted($bust);
    }

    public function isEditable(): bool
    {
        return ! $this->is_preset;
    }

    public function isDeletable(): bool
    {
        return ! $this->is_preset && ! $this->is_active;
    }
}
