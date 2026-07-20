<?php

namespace App\View\Components\Site;

use App\Services\Seo\Seo;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * The public site's entire <head> meta block.
 *
 * A component CLASS rather than logic in Blade because deciding a title,
 * description, canonical, robots directive, and social card is real work, and
 * views in this product hold markup only. The layout passes through whatever
 * :title and :description the page already declared; those act as fallbacks
 * behind the record's own SEO fields.
 *
 * No other template anywhere emits a meta, link rel=canonical, og:, or
 * twitter: tag. One component means one place to fix a mistake.
 */
class Meta extends Component
{
    public array $meta;

    public function __construct(
        private readonly Seo $seo,
        ?string $fallbackTitle = null,
        ?string $fallbackDescription = null,
    ) {
        $this->meta = $seo->resolve($fallbackTitle, $fallbackDescription);
    }

    public function render(): View
    {
        return view('components.site.meta');
    }
}
