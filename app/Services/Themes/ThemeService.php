<?php

namespace App\Services\Themes;

use App\Models\Theme;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Resolves the theme in effect and turns its tokens into CSS.
 *
 * This deliberately rides the mechanism that already exists rather than
 * inventing a second one: x-tailwind-cdn reads resources/css/app.css at runtime
 * and inlines the @theme block for the browser Tailwind build. A theme is just
 * more @theme declarations appended after it, so the browser build resolves
 * them the same way it resolves the shipped tokens, and the whole thing still
 * needs no build step.
 *
 * Only tokens that DIFFER from the shipped defaults are emitted. An install
 * sitting on Civic Navy therefore appends nothing at all and renders byte for
 * byte what it rendered before this feature existed.
 */
class ThemeService
{
    /** Query parameter that previews a theme without activating it. */
    public const PREVIEW_PARAM = 'theme_preview';

    /** Cache key holding the active theme's attributes. */
    public const CACHE_KEY = 'theme.active';

    private ?Theme $resolved = null;
    private bool $didResolve = false;

    public function defaults(): array
    {
        return config('themes.defaults', []);
    }

    /** Tokens merged over the shipped defaults, so a partial theme is complete. */
    public function tokens(?Theme $theme = null): array
    {
        $theme ??= $this->current();

        return array_merge($this->defaults(), array_filter(
            (array) ($theme?->tokens ?? []),
            fn ($v) => $v !== null && $v !== ''
        ));
    }

    /**
     * The theme this request renders with.
     *
     * An admin can append ?theme_preview=<id> to any URL to see a theme live
     * before committing to it. It is gated on the admin role because it is the
     * same power as activating one, just scoped to a single page load.
     */
    public function current(): ?Theme
    {
        if ($this->didResolve) {
            return $this->resolved;
        }
        $this->didResolve = true;

        return $this->resolved = rescue(function () {
            $preview = request()?->query(self::PREVIEW_PARAM);
            if ($preview && auth()->check() && auth()->user()->isAdmin()) {
                if (! Schema::hasTable('themes')) {
                    return null;
                }
                if ($theme = Theme::find($preview)) {
                    return $theme;
                }
            }

            // Cached forever and busted by the model, exactly as the settings
            // map is. Every layout, the favicon link and the token block all ask
            // this question on every page load and none of them should cost a
            // query, let alone a schema lookup.
            $attributes = Cache::rememberForever(self::CACHE_KEY, function () {
                if (! Schema::hasTable('themes')) {
                    return false;
                }

                return Theme::where('is_active', true)->first()?->getAttributes() ?: null;
            });

            // `false` means the table was missing when the cache warmed, which
            // happens between deploy and migrate. Do not pin that forever.
            if ($attributes === false) {
                Cache::forget(self::CACHE_KEY);

                return null;
            }

            return $attributes ? (new Theme)->newFromBuilder($attributes) : null;
        }, null, false);
    }

    public function isPreviewing(): bool
    {
        return (bool) request()?->query(self::PREVIEW_PARAM)
            && auth()->check()
            && auth()->user()->isAdmin();
    }

    /** Effective brand colour, for the components that need just the hex. */
    public function brand(): string
    {
        return $this->tokens()['brand'] ?? (string) config('brand.accent');
    }

    public function accent(): string
    {
        return $this->tokens()['accent'] ?? (string) config('brand.accent_alt');
    }

    public function logoUrl(): ?string
    {
        return ($this->tokens()['logo_url'] ?? '') ?: null;
    }

    public function faviconUrl(): ?string
    {
        return ($this->tokens()['favicon_url'] ?? '') ?: null;
    }

    /**
     * The CSS appended to the inlined app.css token block.
     *
     * Emits nothing when the theme matches the shipped defaults.
     */
    public function css(?Theme $theme = null): string
    {
        $tokens = $this->tokens($theme);
        $defaults = $this->defaults();
        $changed = fn (string $key) => ($tokens[$key] ?? null) !== ($defaults[$key] ?? null);

        $out = '';

        if ($changed('brand')) {
            $out .= $this->ramp('brand', $tokens['brand']);
        }
        if ($changed('accent')) {
            $out .= $this->sealRamp($tokens['accent']);
        }

        $theme_block = '';
        if ($changed('chrome')) {
            $theme_block .= '  --color-chrome: ' . $this->safeColor($tokens['chrome']) . ";\n";
        }
        if ($changed('chrome_soft')) {
            $theme_block .= '  --color-chrome-soft: ' . $this->safeColor($tokens['chrome_soft']) . ";\n";
        }
        if ($changed('font_sans')) {
            $theme_block .= '  --font-sans: ' . $this->safeFont($tokens['font_sans']) . ";\n";
        }
        if ($changed('font_display')) {
            $theme_block .= '  --font-display: ' . $this->safeFont($tokens['font_display']) . ";\n";
        }
        if ($changed('spacing')) {
            $step = round(0.25 * $this->scale($tokens['spacing']), 4);
            $theme_block .= "  --spacing: {$step}rem;\n";
        }
        if ($changed('radius')) {
            $theme_block .= $this->radiusRamp($this->scale($tokens['radius']));
        }

        if ($theme_block !== '') {
            $out .= "\n@theme {\n" . $theme_block . "}\n";
        }

        // Root font size is plain CSS, not a Tailwind token: scaling the root
        // scales every rem the browser build emits, which is what a typography
        // scale is supposed to do.
        if ($changed('font_scale')) {
            $pct = round(100 * $this->scale($tokens['font_scale']), 2);
            $out .= "\n@layer base {\n  html { font-size: {$pct}%; }\n}\n";
        }

        // The public page-head and footer washes are hand-tuned gradients in
        // municipal.css using literal navy hexes, so they do not follow the
        // brand ramp on their own. Re-derive them from the theme's chrome so a
        // retinted site does not keep a navy header on a green palette.
        if ($changed('chrome') || $changed('chrome_soft') || $changed('brand')) {
            $out .= $this->washes($tokens);
        }

        return $out;
    }

    /** Full brand ramp from one colour, matching the shipped scale's feel. */
    private function ramp(string $name, string $color): string
    {
        $c = $this->safeColor($color);
        // Same mix percentages the x-accent-style block uses, so the @theme
        // tokens and the late :root override describe the same ramp rather than
        // two ramps a few percent apart.
        $light = [50 => 92, 100 => 85, 200 => 72, 300 => 55, 400 => 30];
        $dark = [600 => 12, 700 => 25, 800 => 40, 900 => 52, 950 => 68];

        $out = "\n@theme {\n";
        foreach ($light as $step => $mix) {
            $out .= "  --color-{$name}-{$step}: color-mix(in srgb, {$c}, white {$mix}%);\n";
        }
        $out .= "  --color-{$name}-500: {$c};\n";
        foreach ($dark as $step => $mix) {
            $out .= "  --color-{$name}-{$step}: color-mix(in srgb, {$c}, black {$mix}%);\n";
        }

        return $out . "}\n";
    }

    private function sealRamp(string $color): string
    {
        $c = $this->safeColor($color);

        return "\n@theme {\n"
            . "  --color-seal-100: color-mix(in srgb, {$c}, white 82%);\n"
            . "  --color-seal-300: color-mix(in srgb, {$c}, white 45%);\n"
            . "  --color-seal-500: {$c};\n"
            . "  --color-seal-700: color-mix(in srgb, {$c}, black 25%);\n"
            . "}\n";
    }

    /** Tailwind v4's radius ramp, scaled. */
    private function radiusRamp(float $scale): string
    {
        $base = ['xs' => 0.125, 'sm' => 0.25, 'md' => 0.375, 'lg' => 0.5, 'xl' => 0.75, '2xl' => 1.0, '3xl' => 1.5, '4xl' => 2.0];

        $out = '';
        foreach ($base as $key => $rem) {
            $out .= "  --radius-{$key}: " . round($rem * $scale, 4) . "rem;\n";
        }

        return $out;
    }

    private function washes(array $tokens): string
    {
        $chrome = $this->safeColor($tokens['chrome']);
        $soft = $this->safeColor($tokens['chrome_soft']);
        $brand = $this->safeColor($tokens['brand']);

        return "\n@layer base {\n"
            . "  .site-pagehead-wash { background:\n"
            . "    radial-gradient(100% 140% at 0% 0%, {$brand} 0%, transparent 60%),\n"
            . "    linear-gradient(120deg, {$chrome} 0%, {$soft} 60%, {$brand} 100%); }\n"
            . "  .site-footer-wash { background:\n"
            . "    radial-gradient(90% 120% at 100% 0%, {$soft} 0%, transparent 55%),\n"
            . "    linear-gradient(180deg, {$chrome} 0%, color-mix(in srgb, {$chrome}, black 45%) 100%); }\n"
            . "}\n";
    }

    /** Keep an operator-supplied value a plain CSS colour. */
    private function safeColor(string $value): string
    {
        return trim(preg_replace('/[^#0-9a-zA-Z(),.%\s]/', '', $value)) ?: '#0f4c81';
    }

    /** Keep a font stack a font stack. No url(), no semicolons, no braces. */
    private function safeFont(string $value): string
    {
        $clean = preg_replace('/[^0-9a-zA-Z\-\',\.\s]/u', '', $value);

        return trim($clean) ?: 'ui-sans-serif, system-ui, sans-serif';
    }

    private function scale($value): float
    {
        $n = (float) $value;

        return $n > 0 && $n <= 4 ? $n : 1.0;
    }

    // ---------------------------------------------------------------
    // Management
    // ---------------------------------------------------------------

    /** Install the shipped presets. Idempotent: existing slugs are left alone. */
    public function ensurePresets(): void
    {
        rescue(function () {
            if (! Schema::hasTable('themes')) {
                return;
            }

            foreach (config('themes.presets', []) as $index => $preset) {
                Theme::firstOrCreate(
                    ['slug' => $preset['slug']],
                    [
                        'name' => $preset['name'],
                        'description' => $preset['description'] ?? null,
                        'tokens' => $preset['tokens'] ?? [],
                        'is_preset' => true,
                        // First preset is the shipped default and starts active,
                        // but only when no theme is active yet.
                        'is_active' => $index === 0 && ! Theme::where('is_active', true)->exists(),
                    ]
                );
            }

            // Never leave an install with no active theme.
            if (! Theme::where('is_active', true)->exists()) {
                Theme::orderBy('id')->first()?->update(['is_active' => true]);
            }
        }, null, false);
    }

    public function activate(Theme $theme): void
    {
        Theme::where('is_active', true)->update(['is_active' => false]);
        $theme->forceFill(['is_active' => true])->save();
    }

    /** Portable representation handed to another install. */
    public function export(Theme $theme): array
    {
        return [
            'format' => 'municipalmgr.theme',
            'version' => 1,
            'name' => $theme->name,
            'description' => $theme->description,
            'exported_at' => now()->toIso8601String(),
            'tokens' => $this->tokens($theme),
        ];
    }

    /**
     * Build a theme from an exported payload.
     *
     * @throws \InvalidArgumentException when the payload is not a theme file.
     */
    public function import(array $payload, ?int $userId = null): Theme
    {
        if (($payload['format'] ?? null) !== 'municipalmgr.theme') {
            throw new \InvalidArgumentException('That file is not a MunicipalMGR theme export.');
        }
        if (! is_array($payload['tokens'] ?? null)) {
            throw new \InvalidArgumentException('The theme export has no tokens.');
        }

        $name = trim((string) ($payload['name'] ?? 'Imported Theme')) ?: 'Imported Theme';

        return Theme::create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'description' => $payload['description'] ?? null,
            'tokens' => $this->sanitiseTokens($payload['tokens']),
            'is_preset' => false,
            'is_active' => false,
            'created_by' => $userId,
        ]);
    }

    public function duplicate(Theme $theme, ?int $userId = null): Theme
    {
        $name = $theme->name . ' Copy';

        return Theme::create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'description' => $theme->description,
            'tokens' => $this->tokens($theme),
            'is_preset' => false,
            'is_active' => false,
            'created_by' => $userId,
        ]);
    }

    public function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'theme';
        $slug = $base;
        $n = 2;
        while (Theme::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    /** Drop unknown keys and clamp the numeric knobs. Imports are untrusted. */
    public function sanitiseTokens(array $tokens): array
    {
        $allowed = array_keys($this->defaults());
        $clean = [];

        foreach ($allowed as $key) {
            if (! array_key_exists($key, $tokens)) {
                continue;
            }
            $value = $tokens[$key];
            if (! is_scalar($value)) {
                continue;
            }
            $clean[$key] = match ($key) {
                'brand', 'accent', 'chrome', 'chrome_soft' => $this->safeColor((string) $value),
                'font_sans', 'font_display' => $this->safeFont((string) $value),
                'font_scale', 'spacing', 'radius' => (string) $this->scale($value),
                'logo_url', 'favicon_url' => $this->safeUrl((string) $value),
                default => (string) $value,
            };
        }

        return $clean;
    }

    /** Only same-origin paths or plain http(s) URLs may become a logo. */
    private function safeUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (str_starts_with($value, '/')) {
            return $value;
        }

        return preg_match('#^https?://#i', $value) ? $value : '';
    }
}
