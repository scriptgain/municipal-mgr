<?php

namespace App\Services\Templates;

use App\Models\TemplateOverride;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

/**
 * What a municipality is allowed to edit, and how it is grouped on screen.
 *
 * Built by scanning resources/views rather than from a hand-written list, so a
 * template added by a future release shows up without anybody remembering to
 * register it. The group definitions below are the only thing maintained by
 * hand, and an unmatched view lands in "Shared Components" instead of
 * disappearing.
 *
 * This list is also the ALLOWLIST: the controller refuses to edit any view name
 * that does not appear here, which keeps the editor pointed at this app's own
 * templates and away from vendor or framework views.
 */
class TemplateCatalog
{
    /** [label, icon, description, prefixes...] keyed by group id. */
    private const GROUPS = [
        'public' => [
            'label' => 'Public Site',
            'icon' => 'globe',
            'description' => 'Everything a resident sees. Edit these to change the public website.',
            'prefixes' => ['site.', 'components.site.', 'components.layouts.public'],
        ],
        'emails' => [
            'label' => 'Emails',
            'icon' => 'envelope',
            'description' => 'Notification mail sent to staff and residents.',
            'prefixes' => ['emails.'],
        ],
        'admin' => [
            'label' => 'Admin Panel',
            'icon' => 'settings',
            'description' => 'The staff panel. Changes here affect the people running the site, not the public.',
            'prefixes' => ['admin.', 'settings.', 'auth.', 'setup.', 'components.layouts.app', 'components.admin.'],
        ],
        'shared' => [
            'label' => 'Shared Components',
            'icon' => 'folder',
            'description' => 'Buttons, cards, tables, modals. Used by both the public site and the panel, so edit with care.',
            'prefixes' => ['components.'],
        ],
    ];

    /** Views never offered for editing: changing them cannot end well. */
    private const EXCLUDE = [
        'components.tailwind-cdn',
        'components.accent-style',
        'admin.templates.',
        'admin.themes.',
    ];

    /**
     * Every editable template, grouped.
     *
     * @return array<string,array{label:string,icon:string,description:string,items:array}>
     */
    public function groups(): array
    {
        $overridden = TemplateOverride::pluck('updated_at', 'view');

        $groups = [];
        foreach (self::GROUPS as $id => $group) {
            $groups[$id] = $group + ['items' => [], 'id' => $id];
        }

        foreach ($this->scan() as $view) {
            $groupId = $this->groupFor($view);
            $groups[$groupId]['items'][] = [
                'view' => $view,
                'label' => $this->label($view),
                'path' => 'resources/views/' . str_replace('.', '/', $view) . '.blade.php',
                'overridden' => $overridden->has($view),
                'updated_at' => $overridden->get($view),
            ];
        }

        // Drop empty groups and sort each group's items by name.
        foreach ($groups as $id => $group) {
            if (! $group['items']) {
                unset($groups[$id]);
                continue;
            }
            usort($groups[$id]['items'], fn ($a, $b) => strcmp($a['view'], $b['view']));
            $groups[$id]['overridden_count'] = count(array_filter($group['items'], fn ($i) => $i['overridden']));
        }

        return $groups;
    }

    /** Flat list of every editable view name. Used as the allowlist. */
    public function views(): array
    {
        return $this->scan();
    }

    public function isEditable(string $view): bool
    {
        return in_array($view, $this->scan(), true);
    }

    /** The shipped file's contents, or null when the view has no shipped file. */
    public function shippedSource(string $view): ?string
    {
        $path = resource_path('views/' . str_replace('.', '/', $view) . '.blade.php');

        return is_file($path) ? (string) file_get_contents($path) : null;
    }

    /** The source currently in effect: the override if there is one, else shipped. */
    public function effectiveSource(string $view): string
    {
        $override = TemplateOverride::where('view', $view)->value('content');

        return $override ?? (string) $this->shippedSource($view);
    }

    public function label(string $view): string
    {
        $parts = explode('.', $view);
        $last = array_pop($parts);

        return Str::headline(str_replace('_', ' ', ltrim($last, '_')));
    }

    /** Public URL a public-site template can be previewed on, when there is one. */
    public function previewUrl(string $view): ?string
    {
        $map = [
            'site.home' => '/',
            'components.layouts.public' => '/',
        ];

        if (isset($map[$view])) {
            return url($map[$view]);
        }

        // site.news.index -> /news, site.departments.show -> no clean guess.
        if (str_starts_with($view, 'site.') && str_ends_with($view, '.index')) {
            $segment = explode('.', $view)[1] ?? null;

            return $segment ? url('/' . $segment) : null;
        }

        return str_starts_with($view, 'site.') || str_starts_with($view, 'components.site.') ? url('/') : null;
    }

    /** @return string[] dot-notation view names under resources/views. */
    private function scan(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $root = resource_path('views');
        if (! is_dir($root)) {
            return $cache = [];
        }

        $views = [];
        foreach (Finder::create()->files()->in($root)->name('*.blade.php') as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $view = str_replace('/', '.', substr($relative, 0, -strlen('.blade.php')));

            foreach (self::EXCLUDE as $excluded) {
                if ($view === $excluded || str_starts_with($view, $excluded)) {
                    continue 2;
                }
            }

            $views[] = $view;
        }

        sort($views);

        return $cache = $views;
    }

    private function groupFor(string $view): string
    {
        foreach (self::GROUPS as $id => $group) {
            foreach ($group['prefixes'] as $prefix) {
                if ($view === $prefix || str_starts_with($view, $prefix)) {
                    return $id;
                }
            }
        }

        return 'shared';
    }
}
