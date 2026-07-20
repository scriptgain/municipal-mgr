<?php

namespace App\View\Components;

use App\Services\Themes\ThemeService;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Wordmark.
 *
 * Promoted from an anonymous component to a class so the active theme's logo
 * can replace the glyph without the Blade file growing a service lookup. A
 * class component of the same name takes precedence over the .blade.php file,
 * so every existing <x-brand> call site picks this up unchanged.
 */
class Brand extends Component
{
    public ?string $logoUrl;
    public string $icon;

    public function __construct(
        ThemeService $themes,
        public ?string $href = null,
        public ?string $label = null,
        public ?string $sub = null,
    ) {
        $this->logoUrl = rescue(fn () => $themes->logoUrl(), null, false);
        $this->icon = (string) config('brand.icon', 'building');
    }

    public function render(): View
    {
        return view('components.brand');
    }
}
