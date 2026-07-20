<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Typed accessor over the DB Setting store for public-site identity.
 *
 * Views never call Setting::get() directly — they read the values a composer
 * hands them, and the composer reads this. That keeps the "no logic in views"
 * rule intact and gives one place to add a default.
 */
class SiteSettings
{
    /** Defaults applied when the operator has not set a value yet. */
    public const DEFAULTS = [
        'site_name' => null,                 // falls back to config('municipal.site.name')
        'site_kind' => null,                 // City | Town | Village | ...
        'site_state' => null,
        'site_motto' => null,
        'site_logo_path' => null,
        'site_seal_path' => null,
        'site_hero_image_path' => null,
        'site_hero_heading' => null,
        'site_hero_subheading' => null,
        'contact_address' => null,
        'contact_city_state_zip' => null,
        'contact_phone' => null,
        'contact_fax' => null,
        'contact_email' => null,
        'contact_hours' => 'Monday to Friday, 8:00 AM to 5:00 PM',
        'contact_after_hours' => null,
        'contact_map_embed' => null,
        'social_facebook' => null,
        'social_x' => null,
        'social_youtube' => null,
        'social_instagram' => null,
        'social_nextdoor' => null,
        'footer_note' => null,
        'accessibility_contact' => null,
        'pay_bill_url' => null,
        'meeting_stream_url' => null,
    ];

    public static function all(): array
    {
        $stored = Setting::map();
        $out = [];
        foreach (self::DEFAULTS as $key => $default) {
            $value = $stored[$key] ?? null;
            $out[$key] = ($value === null || $value === '') ? $default : $value;
        }

        // Identity falls back to the build-time config so a fresh install is
        // never a page full of blanks.
        $out['site_name'] ??= config('municipal.site.name');
        $out['site_kind'] ??= config('municipal.site.kind');
        $out['site_state'] ??= config('municipal.site.state');
        $out['site_motto'] ??= config('municipal.site.motto');

        return $out;
    }

    public static function get(string $key, $default = null)
    {
        return self::all()[$key] ?? $default;
    }

    /** Persist a batch of site settings (blank string clears back to default). */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if (array_key_exists($key, self::DEFAULTS)) {
                Setting::put($key, $value === null ? null : (string) $value);
            }
        }
    }

    /** "Village of Secor, Arizona" — the full formal name for titles and footers. */
    public static function formalName(): string
    {
        $s = self::all();

        return trim($s['site_name'] . ($s['site_state'] ? ', ' . $s['site_state'] : ''));
    }
}
