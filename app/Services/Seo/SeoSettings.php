<?php

namespace App\Services\Seo;

use App\Models\Setting;

/**
 * Site-wide SEO defaults, stored in the DB Setting store (never .env, per the
 * fleet's DB-driven config rule).
 *
 * Mirrors the shape of App\Services\SiteSettings so both read the same way
 * from a controller, and so views keep receiving plain arrays from a composer
 * instead of calling into a service themselves.
 */
class SeoSettings
{
    /** Defaults applied when the operator has not set a value. */
    public const DEFAULTS = [
        // "%s | Village of Secor" — %s is the page title.
        'seo_title_template' => '%s | %s',
        'seo_default_title' => null,
        'seo_default_description' => null,
        'seo_default_og_image' => null,

        // Staging switch. When on, every public page emits noindex,nofollow and
        // robots.txt disallows everything.
        'seo_discourage' => '0',

        // Search Console / Webmaster verification meta tags.
        'seo_google_verification' => null,
        'seo_bing_verification' => null,
        'seo_pinterest_verification' => null,

        // Social handles used for Twitter cards and sameAs.
        'seo_twitter_site' => null,

        // GovernmentOrganization JSON-LD.
        'seo_organization_type' => 'GovernmentOrganization',
        'seo_structured_data' => '1',

        // Sitemap.
        'seo_sitemap_enabled' => '1',
    ];

    public static function all(): array
    {
        $stored = Setting::map();
        $out = [];
        foreach (self::DEFAULTS as $key => $default) {
            $value = $stored[$key] ?? null;
            $out[$key] = ($value === null || $value === '') ? $default : $value;
        }

        return $out;
    }

    public static function get(string $key, $default = null)
    {
        return self::all()[$key] ?? $default;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if (array_key_exists($key, self::DEFAULTS)) {
                Setting::put($key, $value === null ? null : (string) $value);
            }
        }
    }

    /** Staging mode: discourage every search engine, site wide. */
    public static function discourages(): bool
    {
        return self::get('seo_discourage') === '1';
    }

    public static function structuredDataEnabled(): bool
    {
        return self::get('seo_structured_data') === '1';
    }

    public static function sitemapEnabled(): bool
    {
        return self::get('seo_sitemap_enabled') === '1';
    }
}
