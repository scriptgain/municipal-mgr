<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Seo\SeoSettings;
use Illuminate\Http\Response;

/**
 * robots.txt, generated rather than shipped as a static file.
 *
 * It has to be dynamic for one reason above all: the staging switch. An
 * operator standing a site up on a temporary hostname flips "Discourage Search
 * Engines" once, and both the meta robots tag and this file agree immediately.
 * A checked-in public/robots.txt cannot do that, which is why the static one
 * is removed as part of this feature.
 */
class RobotsController extends Controller
{
    /**
     * Paths kept out of crawl budget. Staff surfaces, token-protected resident
     * pages, and the two sensitive optional modules (disallowed whether or not
     * they are currently enabled, so turning one on is never a disclosure).
     */
    private const DISALLOW = [
        '/admin',
        '/track',
        '/report-an-issue/submitted',
        '/search',
        '/arrest-records',
        '/inmate-roster',
        '/pay',
        '/brand/',
    ];

    public function __invoke(): Response
    {
        $lines = ['User-agent: *'];

        if (SeoSettings::discourages()) {
            // Staging. Nothing is crawlable and no sitemap is advertised.
            $lines[] = 'Disallow: /';

            return $this->text($lines);
        }

        foreach (self::DISALLOW as $path) {
            $lines[] = 'Disallow: ' . $path;
        }
        $lines[] = 'Allow: /';

        if (SeoSettings::sitemapEnabled()) {
            $lines[] = '';
            $lines[] = 'Sitemap: ' . route('sitemap.index');
        }

        return $this->text($lines);
    }

    private function text(array $lines): Response
    {
        return response(implode("\n", $lines) . "\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
