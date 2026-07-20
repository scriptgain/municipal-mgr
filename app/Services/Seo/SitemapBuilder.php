<?php

namespace App\Services\Seo;

use App\Models\Bid;
use App\Models\Department;
use App\Models\Event;
use App\Models\FileItem;
use App\Models\FormDefinition;
use App\Models\JobPosting;
use App\Models\Meeting;
use App\Models\NewsPost;
use App\Models\Notice;
use App\Models\Official;
use App\Models\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds sitemap.xml and its per-section children.
 *
 * A sitemap INDEX is served at /sitemap.xml rather than one flat urlset. A
 * municipal file library is the one collection here that genuinely grows
 * without bound (every agenda, budget, and ordinance a town has ever posted),
 * and splitting by section also means a crawler re-fetching /sitemap-news.xml
 * is not re-reading ten thousand document URLs.
 *
 * Two rules hold everywhere: a URL appears only if it actually resolves for an
 * anonymous visitor, and lastmod is a real stored timestamp or is omitted.
 * A sitemap that lies about freshness is worse than one that says nothing.
 */
class SitemapBuilder
{
    /** URLs per child sitemap. The spec allows 50,000; this stays well clear. */
    public const CHUNK = 5000;

    /** Section keys in the order they appear in the index. */
    public const SECTIONS = [
        'core', 'pages', 'departments', 'news', 'notices', 'events',
        'meetings', 'officials', 'jobs', 'bids', 'forms', 'files',
    ];

    public function isSection(string $section): bool
    {
        return in_array($section, self::SECTIONS, true);
    }

    /* ------------------------------------------------------------------ */
    /* Documents                                                           */
    /* ------------------------------------------------------------------ */

    /** The sitemap index: one <sitemap> per non-empty section chunk. */
    public function index(): string
    {
        $lines = [];

        foreach (self::SECTIONS as $section) {
            $entries = $this->entries($section);
            if ($entries->isEmpty()) {
                continue;
            }

            $chunks = $entries->chunk(self::CHUNK)->values();
            foreach ($chunks as $i => $chunk) {
                $page = $i + 1;
                $loc = $chunks->count() > 1
                    ? route('sitemap.section.page', ['section' => $section, 'page' => $page])
                    : route('sitemap.section', ['section' => $section]);

                $lastmod = $chunk->pluck('lastmod')->filter()->max();

                $lines[] = '  <sitemap>'
                    . "\n    <loc>" . $this->esc($loc) . '</loc>'
                    . ($lastmod ? "\n    <lastmod>" . $this->esc($lastmod) . '</lastmod>' : '')
                    . "\n  </sitemap>";
            }
        }

        return $this->document('sitemapindex', $lines);
    }

    /** One section's urlset, optionally a single chunk of it. */
    public function urlset(string $section, int $page = 1): ?string
    {
        $entries = $this->entries($section);
        if ($entries->isEmpty()) {
            return null;
        }

        $chunks = $entries->chunk(self::CHUNK)->values();
        $chunk = $chunks->get($page - 1);
        if ($chunk === null) {
            return null;
        }

        $lines = $chunk->map(function (array $entry) {
            return '  <url>'
                . "\n    <loc>" . $this->esc($entry['loc']) . '</loc>'
                . ($entry['lastmod'] ? "\n    <lastmod>" . $this->esc($entry['lastmod']) . '</lastmod>' : '')
                . ($entry['changefreq'] ? "\n    <changefreq>" . $this->esc($entry['changefreq']) . '</changefreq>' : '')
                . ($entry['priority'] ? "\n    <priority>" . $this->esc($entry['priority']) . '</priority>' : '')
                . "\n  </url>";
        })->all();

        return $this->document('urlset', $lines);
    }

    /** Total public URLs across every section. Used by the admin health view. */
    public function totalUrls(): int
    {
        return collect(self::SECTIONS)->sum(fn (string $s) => $this->entries($s)->count());
    }

    /** Per-section URL counts for the admin health view. */
    public function counts(): array
    {
        $out = [];
        foreach (self::SECTIONS as $section) {
            $out[$section] = $this->entries($section)->count();
        }

        return $out;
    }

    /* ------------------------------------------------------------------ */
    /* Entries                                                             */
    /* ------------------------------------------------------------------ */

    /** @return Collection<int, array{loc:string,lastmod:?string,changefreq:?string,priority:?string}> */
    public function entries(string $section): Collection
    {
        return match ($section) {
            'core' => $this->coreEntries(),
            'pages' => $this->fromModels(
                Page::published()->orderBy('id')->get(), 'weekly', '0.7'
            ),
            'departments' => $this->fromModels(
                Department::published()->orderBy('id')->get(), 'monthly', '0.8'
            ),
            'news' => $this->fromModels(
                NewsPost::published()->orderBy('id')->get(), 'monthly', '0.6'
            ),
            'notices' => $this->fromModels(
                Notice::where('status', 'published')->orderBy('id')->get(), 'weekly', '0.6'
            ),
            'events' => $this->fromModels(
                Event::published()->orderBy('id')->get(), 'weekly', '0.5'
            ),
            'meetings' => $this->fromModels(
                Meeting::published()->orderBy('id')->get(), 'monthly', '0.6'
            ),
            'jobs' => $this->fromModels(
                JobPosting::where('status', 'published')->orderBy('id')->get(), 'daily', '0.7'
            ),
            'bids' => $this->fromModels(
                Bid::published()->orderBy('id')->get(), 'daily', '0.6'
            ),
            'files' => $this->fromModels(
                FileItem::publiclyVisible()->with('folder')->orderBy('id')->get(), 'monthly', '0.4'
            ),
            'officials' => $this->officialEntries(),
            'forms' => $this->formEntries(),
            default => collect(),
        };
    }

    /**
     * The hand-written public routes: landing pages and the civic tasks that
     * are not backed by a content record.
     *
     * Deliberately excluded: /search (an empty results page is nothing to
     * index), /track and its token URLs (a resident's private request status),
     * and every POST-only endpoint.
     */
    private function coreEntries(): Collection
    {
        $freshest = fn (string $model, string $column = 'updated_at') => $model::query()->max($column);

        $routes = [
            ['site.home', 'daily', '1.0', $freshest(NewsPost::class)],
            ['site.news', 'daily', '0.8', $freshest(NewsPost::class)],
            ['site.notices', 'daily', '0.8', $freshest(Notice::class)],
            ['site.events', 'daily', '0.7', $freshest(Event::class)],
            ['site.calendar', 'daily', '0.6', $freshest(Event::class)],
            ['site.departments', 'monthly', '0.9', $freshest(Department::class)],
            ['site.directory', 'monthly', '0.7', null],
            ['site.government', 'monthly', '0.8', $freshest(Official::class)],
            ['site.meetings', 'weekly', '0.8', $freshest(Meeting::class)],
            ['site.files', 'weekly', '0.7', $freshest(FileItem::class)],
            ['site.jobs', 'daily', '0.8', $freshest(JobPosting::class)],
            ['site.bids', 'daily', '0.7', $freshest(Bid::class)],
            ['site.report', 'monthly', '0.9', null],
            ['site.contact', 'monthly', '0.8', null],
            ['site.accessibility', 'yearly', '0.3', null],
        ];

        return collect($routes)
            ->filter(fn (array $r) => \Illuminate\Support\Facades\Route::has($r[0]))
            ->map(fn (array $r) => [
                'loc' => route($r[0]),
                'lastmod' => $this->stamp($r[3]),
                'changefreq' => $r[1],
                'priority' => $r[2],
            ])->values();
    }

    /**
     * Turn SEO-aware records into entries.
     *
     * seoIsPublic() is the single gate: it already folds in the noindex flag,
     * the publish state, and (for files) staff-only visibility, so a record
     * excluded from the index can never leak into the sitemap.
     */
    private function fromModels(EloquentCollection $records, string $changefreq, string $priority): Collection
    {
        return $records
            ->filter(fn ($record) => $record->seoIsPublic())
            ->map(fn ($record) => [
                'loc' => $record->seoUrl(),
                'lastmod' => $this->stamp($record->seoLastModified()),
                'changefreq' => $changefreq,
                'priority' => $priority,
            ])
            ->filter(fn (array $entry) => ! empty($entry['loc']))
            ->values();
    }

    /** Elected officials have public profile pages but no SEO columns yet. */
    private function officialEntries(): Collection
    {
        return Official::published()->orderBy('id')->get()
            ->map(fn (Official $o) => [
                'loc' => route('site.government.show', $o),
                'lastmod' => $this->stamp($o->updated_at),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ])->values();
    }

    /** Published forms are real public pages residents search for by name. */
    private function formEntries(): Collection
    {
        return FormDefinition::published()->orderBy('id')->get()
            ->map(fn (FormDefinition $f) => [
                'loc' => route('site.forms.show', $f->slug),
                'lastmod' => $this->stamp($f->updated_at),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ])->values();
    }

    /* ------------------------------------------------------------------ */
    /* Formatting                                                          */
    /* ------------------------------------------------------------------ */

    /** W3C datetime, or null when there is no real timestamp to report. */
    private function stamp($value): ?string
    {
        if (! $value) {
            return null;
        }
        if ($value instanceof Carbon || $value instanceof \DateTimeInterface) {
            return Carbon::instance(
                $value instanceof Carbon ? $value->toDateTime() : $value
            )->toAtomString();
        }

        try {
            return Carbon::parse((string) $value)->toAtomString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function document(string $root, array $lines): string
    {
        $attributes = $root === 'sitemapindex'
            ? 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            : 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . "<{$root} {$attributes}>\n"
            . implode("\n", $lines)
            . ($lines ? "\n" : '')
            . "</{$root}>\n";
    }

    /** XML-escape a value. Ampersands in filter URLs are the usual culprit. */
    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
