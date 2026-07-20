<?php

namespace App\View\Components;

use App\Services\Themes\ThemeService;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Assembles the Tailwind v4 browser-build token block.
 *
 * This is a component CLASS rather than logic inside the Blade file because the
 * token assembly is real work — reading app.css, stripping build-only
 * directives, and generating the brand ramp — and views in this product hold
 * markup only.
 */
class TailwindCdn extends Component
{
    public string $tokens;

    public function __construct(private ThemeService $themes)
    {
        $this->tokens = $this->buildTokens();
    }

    private function buildTokens(): string
    {
        $css = @file_get_contents(resource_path('css/app.css')) ?: '';

        // Drop @import "tailwindcss"; and @source globs — the browser build
        // supplies Tailwind itself and scans the live DOM rather than files.
        $css = preg_replace('/^[ \t]*@(?:import|source)\b[^;]*;[ \t]*\R?/m', '', $css);

        // Bake the operator's accent straight into the compiled theme so the
        // browser build emits the brand colour itself. Doing it here rather
        // than only in a separate <style> block avoids a cascade race against
        // the CSS the browser build injects at runtime.
        $accent = (string) config('brand.accent');
        $accentAlt = (string) config('brand.accent_alt');

        if ($accent !== '' && strtolower($accent) !== '#0f4c81') {
            $css .= $this->ramp('brand', $accent);
        }
        if ($accentAlt !== '' && strtolower($accentAlt) !== '#c8a45c') {
            $css .= "\n@theme {\n"
                . "  --color-seal-100: color-mix(in srgb, {$this->safe($accentAlt)}, white 82%);\n"
                . "  --color-seal-300: color-mix(in srgb, {$this->safe($accentAlt)}, white 45%);\n"
                . "  --color-seal-500: {$this->safe($accentAlt)};\n"
                . "  --color-seal-700: color-mix(in srgb, {$this->safe($accentAlt)}, black 25%);\n"
                . "}\n";
        }

        // The active (or previewed) theme is appended LAST so its tokens win
        // over both app.css and the Branding accent. It emits nothing at all
        // while the install sits on the shipped default theme.
        $css .= rescue(fn () => $this->themes->css(), '', false);

        return $css;
    }

    private function ramp(string $name, string $color): string
    {
        $c = $this->safe($color);
        $steps = [
            50 => 'white 94%', 100 => 'white 87%', 200 => 'white 74%',
            300 => 'white 56%', 400 => 'white 30%',
        ];
        $out = "\n@theme {\n";
        foreach ($steps as $step => $mix) {
            $out .= "  --color-{$name}-{$step}: color-mix(in srgb, {$c}, {$mix});\n";
        }
        $out .= "  --color-{$name}-500: {$c};\n";
        foreach ([600 => 'black 12%', 700 => 'black 25%', 800 => 'black 40%', 900 => 'black 55%', 950 => 'black 70%'] as $step => $mix) {
            $out .= "  --color-{$name}-{$step}: color-mix(in srgb, {$c}, {$mix});\n";
        }

        return $out . "}\n";
    }

    /** Keep an operator-supplied colour a plain CSS colour value. */
    private function safe(string $value): string
    {
        return preg_replace('/[^#0-9a-zA-Z(),.% ]/', '', $value);
    }

    public function render(): View
    {
        return view('components.tailwind-cdn');
    }
}
