<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Seo\SeoSettings;
use App\Services\Seo\SitemapBuilder;
use Illuminate\Http\Response;

/**
 * sitemap.xml and its per-section children.
 *
 * Public and ungated, like the rest of the municipal site: a search engine
 * must be able to read it while the panel is mid-setup or the license cannot
 * be verified.
 */
class SitemapController extends Controller
{
    public function __construct(private readonly SitemapBuilder $builder)
    {
    }

    /** The sitemap index at /sitemap.xml. */
    public function index(): Response
    {
        $this->guard();

        return $this->xml($this->builder->index());
    }

    /** One section, e.g. /sitemap-news.xml or /sitemap-files-2.xml. */
    public function section(string $section, int $page = 1): Response
    {
        $this->guard();

        abort_unless($this->builder->isSection($section), 404);

        $body = $this->builder->urlset($section, max(1, $page));
        abort_if($body === null, 404);

        return $this->xml($body);
    }

    /**
     * A site in staging has asked search engines to stay away, so it must not
     * hand them a map of itself either.
     */
    private function guard(): void
    {
        abort_unless(SeoSettings::sitemapEnabled(), 404);
        abort_if(SeoSettings::discourages(), 404);
    }

    private function xml(string $body): Response
    {
        return response($body, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
