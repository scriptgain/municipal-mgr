<?php

namespace App\View\Components;

use App\Services\Themes\ThemeService;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Late brand-ramp override, emitted after the browser Tailwind build's styles.
 *
 * Two things can retint the brand: the Branding settings screen (a single
 * accent hex) and the Theme Manager (a whole token set). The theme wins,
 * because a theme is the more specific, more deliberate choice. Resolving that
 * precedence here in a class rather than in the Blade file keeps the template
 * markup-only and gives both consumers one answer to the question "what colour
 * is this install actually using".
 */
class AccentStyle extends Component
{
    public string $accent;
    public bool $needed;

    public function __construct(ThemeService $themes)
    {
        $shipped = (string) config('themes.defaults.brand', '#0f4c81');
        $themeBrand = $themes->brand();

        // A theme that moves the brand off the shipped value is authoritative.
        // Otherwise fall back to the Branding screen's accent.
        $this->accent = strtolower($themeBrand) !== strtolower($shipped)
            ? $themeBrand
            : (string) config('brand.accent', $shipped);

        // Emitted whenever there is an accent at all, which is what shipped and
        // therefore what the live site currently renders. Suppressing it on the
        // default colour would quietly shift every brand shade to app.css's
        // hand-tuned ramp, and "add a theme manager" must not repaint a site
        // that has not chosen a theme.
        $this->needed = $this->accent !== '';
    }

    public function render(): View
    {
        return view('components.accent-style');
    }
}
