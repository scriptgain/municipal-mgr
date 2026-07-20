<?php

namespace App\Services\Seo;

use App\Models\Bid;
use App\Models\Department;
use App\Models\Event;
use App\Models\FileItem;
use App\Models\JobPosting;
use App\Models\Meeting;
use App\Models\NewsPost;
use App\Models\Notice;
use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * The SEO health check behind Settings -> SEO Health.
 *
 * Everything it reports is something an editor can actually fix in the panel,
 * and every finding links straight to the record's edit screen. It reads the
 * EFFECTIVE values (what the site really emits after fallbacks), not the raw
 * columns, so an unedited page with a perfectly good derived description is
 * not reported as a problem.
 */
class SeoAudit
{
    /** Google shows roughly this much. Outside the range is a real warning. */
    public const DESC_MIN = 70;
    public const DESC_MAX = 160;
    public const TITLE_MAX = 60;

    /** [model, human label, edit route name]. */
    private const SOURCES = [
        [Page::class, 'Page', 'pages.edit'],
        [NewsPost::class, 'News Post', 'news.edit'],
        [Notice::class, 'Public Notice', 'notices.edit'],
        [Event::class, 'Event', 'events.edit'],
        [Department::class, 'Department', 'departments.edit'],
        [Meeting::class, 'Meeting', 'meetings.edit'],
        [JobPosting::class, 'Job Posting', 'jobs.edit'],
        [Bid::class, 'Bid Or RFP', 'bids.edit'],
        [FileItem::class, 'File', 'files.edit'],
    ];

    /**
     * Run every check.
     *
     * @return array{summary: array, groups: array}
     */
    public function run(): array
    {
        $records = $this->publicRecords();

        $missingDescription = [];
        $shortDescription = [];
        $longDescription = [];
        $longTitle = [];
        $noCustomTitle = [];
        $hidden = [];

        foreach ($records as $row) {
            $record = $row['record'];

            $description = trim((string) $record->seoDescription());
            $title = trim((string) $record->seoTitle());

            if ($description === '') {
                $missingDescription[] = $row + ['detail' => 'Falls back to the site-wide description.'];
            } elseif (mb_strlen($description) < self::DESC_MIN) {
                $shortDescription[] = $row + ['detail' => mb_strlen($description) . ' characters. Aim for ' . self::DESC_MIN . ' to ' . self::DESC_MAX . '.'];
            } elseif (mb_strlen($description) > self::DESC_MAX) {
                $longDescription[] = $row + ['detail' => mb_strlen($description) . ' characters. Search results will cut it off.'];
            }

            if ($title === '') {
                $missingDescription[] = $row + ['detail' => 'This record has no usable title at all.'];
            } elseif (mb_strlen($title) > self::TITLE_MAX) {
                $longTitle[] = $row + ['detail' => mb_strlen($title) . ' characters before the site name is added.'];
            }

            if (trim((string) $record->meta_title) === '') {
                $noCustomTitle[] = $row + ['detail' => 'Using the record title: "' . $title . '"'];
            }
        }

        foreach ($this->hiddenRecords() as $row) {
            $hidden[] = $row + ['detail' => 'Hidden from search engines and excluded from sitemap.xml.'];
        }

        $duplicates = $this->duplicateTitles($records);

        $groups = [
            'missing' => [
                'label' => 'Missing Descriptions',
                'help' => 'Nothing in the record produced a description, so these pages fall back to the site-wide text. Two pages with the same description compete with each other.',
                'tone' => 'critical',
                'rows' => $missingDescription,
            ],
            'duplicates' => [
                'label' => 'Duplicate Titles',
                'help' => 'Several pages would appear in search results under the same heading. Give each one a distinct Search Engine Title.',
                'tone' => 'critical',
                'rows' => $duplicates,
            ],
            'long' => [
                'label' => 'Descriptions Too Long',
                'help' => 'Over ' . self::DESC_MAX . ' characters. The end will be replaced with an ellipsis in results.',
                'tone' => 'warning',
                'rows' => $longDescription,
            ],
            'short' => [
                'label' => 'Descriptions Too Short',
                'help' => 'Under ' . self::DESC_MIN . ' characters. There is room to say more about what the page offers.',
                'tone' => 'warning',
                'rows' => $shortDescription,
            ],
            'titles' => [
                'label' => 'Titles Too Long',
                'help' => 'Over ' . self::TITLE_MAX . ' characters before the site name is appended, so the title will be truncated.',
                'tone' => 'warning',
                'rows' => $longTitle,
            ],
            'no_custom_title' => [
                'label' => 'No Custom Title',
                'help' => 'These use the record title, which is usually correct. Listed so you can spot the ones worth rewording for search.',
                'tone' => 'info',
                'rows' => $noCustomTitle,
            ],
            'hidden' => [
                'label' => 'Hidden From Search',
                'help' => 'Deliberately excluded. Check nothing here should be findable.',
                'tone' => 'info',
                'rows' => $hidden,
            ],
        ];

        $problems = count($missingDescription) + count($duplicates)
            + count($longDescription) + count($shortDescription) + count($longTitle);

        return [
            'summary' => [
                'indexable' => count($records),
                'problems' => $problems,
                'critical' => count($missingDescription) + count($duplicates),
                'hidden' => count($hidden),
            ],
            'groups' => $groups,
        ];
    }

    /* ------------------------------------------------------------------ */

    /**
     * Titles that more than one record would render.
     *
     * Compared case-insensitively on the EFFECTIVE title, because two pages
     * called "Permits" and "permits" collide in search results just the same.
     */
    private function duplicateTitles(array $records): array
    {
        $byTitle = [];
        foreach ($records as $row) {
            $key = mb_strtolower(trim((string) $row['record']->seoTitle()));
            if ($key === '') {
                continue;
            }
            $byTitle[$key][] = $row;
        }

        $out = [];
        foreach ($byTitle as $rows) {
            if (count($rows) < 2) {
                continue;
            }
            foreach ($rows as $row) {
                $out[] = $row + ['detail' => 'Shared with ' . (count($rows) - 1) . ' other record(s).'];
            }
        }

        return $out;
    }

    /** Every record a search engine can currently reach. */
    private function publicRecords(): array
    {
        return $this->collect(fn (Model $record) => $record->seoIsPublic());
    }

    /** Records excluded from search by their own noindex flag. */
    private function hiddenRecords(): array
    {
        return $this->collect(fn (Model $record) => $record->seoNoindex());
    }

    /** @param callable(Model): bool $filter */
    private function collect(callable $filter): array
    {
        $out = [];

        foreach (self::SOURCES as [$model, $label, $editRoute]) {
            /** @var Collection $records */
            $records = $model::query()->orderBy('id')->get();

            foreach ($records as $record) {
                if (! $filter($record)) {
                    continue;
                }

                $out[] = [
                    'type' => $label,
                    'title' => $record->seoDefaultTitle() ?: ('#' . $record->getKey()),
                    'edit_url' => $this->editUrl($editRoute, $record),
                    'public_url' => $record->seoUrl(),
                    'record' => $record,
                ];
            }
        }

        return $out;
    }

    /**
     * Admin edit link. Every one of these models is slug-bound (HasSlug sets
     * getRouteKeyName), files included, so the route key is always correct.
     */
    private function editUrl(string $route, Model $record): ?string
    {
        if (! \Illuminate\Support\Facades\Route::has($route)) {
            return null;
        }

        return route($route, $record->getRouteKey());
    }
}
