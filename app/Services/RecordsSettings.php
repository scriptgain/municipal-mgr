<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Typed accessor over the DB Setting store for the Jail And Arrest Records
 * module (fleet rule: operator knobs live in the DB, never .env).
 *
 * Everything about this module is gated on enabled(). While it is false the
 * public routes 404, the module contributes nothing to either navigation, and
 * the only thing a staff member can see is the settings screen that turns it
 * on. That is deliberate: an install that has not consciously accepted the
 * legal responsibility of publishing arrest data should not be publishing it.
 */
class RecordsSettings
{
    public const DEFAULT_DISCLAIMER = 'An arrest is not a conviction. Every person named here is presumed innocent unless and until proven guilty in a court of law. These records reflect the information available at the time of booking and may not reflect the charges ultimately filed, the current status of a case, or its outcome.';

    /** Setting key => default. Defaults are the safe position, not the convenient one. */
    public const DEFAULTS = [
        'records_module_enabled' => '0',   // the whole module, off out of the box
        'records_mugshots_enabled' => '0', // several states restrict publication
        'records_retention_days' => null,  // falls back to config
        'records_show_bond' => '1',
        'records_show_case_number' => '1',
        'records_public_search_enabled' => '1',
        'records_roster_enabled' => '1',
        'records_disclaimer' => null,      // falls back to DEFAULT_DISCLAIMER
        'records_blotter_intro' => null,
        'records_takedown_contact' => null,
        'records_agency_name' => null,     // "Cottonwood Springs Police Department"
    ];

    public static function all(): array
    {
        $stored = Setting::map();
        $out = [];
        foreach (self::DEFAULTS as $key => $default) {
            $value = $stored[$key] ?? null;
            $out[$key] = ($value === null || $value === '') ? $default : $value;
        }

        $out['records_retention_days'] ??= (string) config('records.default_retention_days', 60);
        $out['records_disclaimer'] ??= self::DEFAULT_DISCLAIMER;

        return $out;
    }

    public static function get(string $key, $default = null)
    {
        return self::all()[$key] ?? $default;
    }

    public static function enabled(): bool
    {
        return self::get('records_module_enabled') === '1';
    }

    public static function mugshotsEnabled(): bool
    {
        return self::get('records_mugshots_enabled') === '1';
    }

    public static function rosterEnabled(): bool
    {
        return self::get('records_roster_enabled') === '1';
    }

    public static function retentionDays(): int
    {
        $days = (int) self::get('records_retention_days');

        return $days > 0 ? $days : (int) config('records.default_retention_days', 60);
    }

    public static function disclaimer(): string
    {
        return (string) self::get('records_disclaimer');
    }

    /** Persist a batch of module settings (blank clears back to the default). */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if (array_key_exists($key, self::DEFAULTS)) {
                Setting::put($key, $value === null ? null : (string) $value);
            }
        }
    }

    /**
     * Add or hide the public menu entry for the blotter.
     *
     * Deliberately a MENU ITEM under an existing dropdown rather than a new
     * top-level link: the primary navigation is already at its comfortable
     * limit and another root item would push it past readable.
     *
     * Hiding unpublishes rather than deletes, so an operator who toggles the
     * module off and back on does not lose their placement or wording.
     */
    public static function syncPublicMenu(bool $enabled): void
    {
        $url = route('site.records.blotter');

        $item = MenuItem::where('menu', 'primary')->where('url', $url)->first();

        if (! $enabled) {
            $item?->update(['is_published' => false]);
            self::forgetMenuCache();

            return;
        }

        // Prefer Services; fall back to Government. If neither dropdown exists
        // we do NOT create a root-level item: an operator who has rebuilt the
        // nav gets to place it themselves in Navigation Menus.
        $parent = MenuItem::where('menu', 'primary')->whereNull('parent_id')
            ->whereIn('label', ['Services', 'Government'])
            ->orderByRaw("CASE WHEN label = 'Services' THEN 0 ELSE 1 END")
            ->first();

        if (! $parent && ! $item) {
            return;
        }

        if ($item) {
            $item->update(['is_published' => true]);
        } else {
            MenuItem::create([
                'menu' => 'primary',
                'parent_id' => $parent->id,
                'label' => 'Arrest Records And Inmate Roster',
                'url' => $url,
                'icon' => 'shield',
                'description' => 'Recent bookings and the current custody list.',
                'sort_order' => (int) MenuItem::where('parent_id', $parent->id)->max('sort_order') + 1,
                'is_published' => true,
            ]);
        }

        self::forgetMenuCache();
    }

    private static function forgetMenuCache(): void
    {
        foreach (['primary', 'footer', 'quicklinks', 'utility'] as $menu) {
            Cache::forget("site.menu.{$menu}");
        }
    }
}
