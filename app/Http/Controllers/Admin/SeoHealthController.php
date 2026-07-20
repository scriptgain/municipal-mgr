<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Seo\SeoAudit;
use App\Services\Seo\SeoSettings;
use App\Services\Seo\SitemapBuilder;

/**
 * Settings -> SEO Health. Read-only: every finding links to the record that
 * fixes it rather than offering a bulk action, because a good description is
 * written per page, not generated in bulk.
 */
class SeoHealthController extends Controller
{
    public function __invoke(SeoAudit $audit, SitemapBuilder $sitemap)
    {
        abort_unless(auth()->user()->canEditContent(), 403);

        $report = $audit->run();

        return view('settings.seo-health', [
            'summary' => $report['summary'],
            'groups' => $report['groups'],
            'discouraged' => SeoSettings::discourages(),
            'sitemapEnabled' => SeoSettings::sitemapEnabled(),
            'sitemapUrl' => route('sitemap.index'),
            'sitemapTotal' => $sitemap->totalUrls(),
        ]);
    }
}
