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
use App\Services\SiteSettings;
use Illuminate\Database\Eloquent\Model;

/**
 * The one place a public page's meta is decided.
 *
 * Controllers hand this the record they are rendering (or a couple of plain
 * strings for the index screens) and the Meta view component asks it for the
 * finished tags. No Blade template anywhere builds a title, a canonical, or an
 * og: tag by hand, which is what keeps the whole surface consistent when a new
 * content type is added.
 *
 * Bound as a singleton for the life of one request.
 */
class Seo
{
    private ?Model $entity = null;

    private array $overrides = [];

    private array $breadcrumbs = [];

    private array $extraSchema = [];

    /**
     * Section each entity type hangs off, used for the BreadcrumbList when a
     * controller does not supply its own trail. [label, route name].
     */
    private const SECTIONS = [
        NewsPost::class => ['News', 'site.news'],
        Notice::class => ['Public Notices', 'site.notices'],
        Event::class => ['Events', 'site.events'],
        Department::class => ['Departments', 'site.departments'],
        Meeting::class => ['Meetings', 'site.meetings'],
        JobPosting::class => ['Employment', 'site.jobs'],
        Bid::class => ['Bids And RFPs', 'site.bids'],
        FileItem::class => ['Files', 'site.files'],
    ];

    /* ------------------------------------------------------------------ */
    /* Collection                                                          */
    /* ------------------------------------------------------------------ */

    /** Attach the record this page is about. */
    public function for(?Model $entity): static
    {
        $this->entity = $entity;

        return $this;
    }

    /** Override any resolved value: title, description, image, canonical, type. */
    public function set(array $values): static
    {
        $this->overrides = array_merge($this->overrides, array_filter(
            $values,
            fn ($v) => $v !== null && $v !== ''
        ));

        return $this;
    }

    /** Force this page out of the index regardless of the record's own flag. */
    public function noindex(bool $on = true): static
    {
        $this->overrides['noindex'] = $on;

        return $this;
    }

    /** Breadcrumb trail as [['label' => ..., 'url' => ...], ...]. */
    public function breadcrumbs(array $crumbs): static
    {
        $this->breadcrumbs = $crumbs;

        return $this;
    }

    /** Add a hand-built JSON-LD node to this page's graph. */
    public function schema(array $node): static
    {
        $this->extraSchema[] = $node;

        return $this;
    }

    public function entity(): ?Model
    {
        return $this->entity;
    }

    /* ------------------------------------------------------------------ */
    /* Resolution                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Everything the <head> needs, already escaped-ready and with nulls
     * removed, so the Blade template is a pair of foreach loops.
     *
     * @param  string|null  $fallbackTitle  the :title the page passed the layout
     * @param  string|null  $fallbackDescription  the :description it passed
     */
    public function resolve(?string $fallbackTitle = null, ?string $fallbackDescription = null): array
    {
        $settings = SeoSettings::all();
        $site = SiteSettings::all();
        $siteName = (string) $site['site_name'];
        $formalName = SiteSettings::formalName();

        $pageTitle = $this->pageTitle($fallbackTitle, $settings);
        $description = $this->description($fallbackDescription, $settings, $site, $formalName);
        $canonical = $this->canonical();
        $image = $this->imageUrl($settings);
        $robots = $this->robots($settings);

        return [
            'title' => $this->documentTitle($pageTitle, $siteName, $formalName, $settings),
            'page_title' => $pageTitle,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'og' => array_filter([
                'og:type' => $this->ogType(),
                'og:title' => $pageTitle ?: $formalName,
                'og:description' => $description,
                'og:url' => $canonical,
                'og:site_name' => $siteName,
                'og:locale' => 'en_US',
                'og:image' => $image,
            ], fn ($v) => $v !== null && $v !== ''),
            'twitter' => array_filter([
                'twitter:card' => $image ? 'summary_large_image' : 'summary',
                'twitter:title' => $pageTitle ?: $formalName,
                'twitter:description' => $description,
                'twitter:image' => $image,
                'twitter:site' => $settings['seo_twitter_site'],
            ], fn ($v) => $v !== null && $v !== ''),
            'verification' => array_filter([
                'google-site-verification' => $settings['seo_google_verification'],
                'msvalidate.01' => $settings['seo_bing_verification'],
                'p:domain_verify' => $settings['seo_pinterest_verification'],
            ], fn ($v) => $v !== null && $v !== ''),
            'json_ld' => $this->jsonLd($settings, $pageTitle, $description, $canonical, $image),
        ];
    }

    /* ------------------------------------------------------------------ */

    /** The page's own title, before the site name is appended. */
    private function pageTitle(?string $fallback, array $settings): ?string
    {
        if (! empty($this->overrides['title'])) {
            return (string) $this->overrides['title'];
        }
        if ($this->entity && method_exists($this->entity, 'seoTitle')) {
            $fromEntity = trim($this->entity->seoTitle());
            if ($fromEntity !== '') {
                return $fromEntity;
            }
        }
        if ($fallback !== null && trim($fallback) !== '') {
            return trim($fallback);
        }

        return $settings['seo_default_title'] ? (string) $settings['seo_default_title'] : null;
    }

    /**
     * Assemble the <title>. The homepage and any page whose title is already
     * the municipality's name render the formal name alone rather than
     * "Secor | Secor".
     */
    private function documentTitle(?string $pageTitle, string $siteName, string $formalName, array $settings): string
    {
        if ($pageTitle === null || $pageTitle === '' || $pageTitle === $siteName) {
            return $formalName;
        }

        $template = (string) ($settings['seo_title_template'] ?: '%s | %s');

        return trim(sprintf($template, $pageTitle, $siteName));
    }

    private function description(?string $fallback, array $settings, array $site, string $formalName): ?string
    {
        if (! empty($this->overrides['description'])) {
            return (string) $this->overrides['description'];
        }
        if ($this->entity && method_exists($this->entity, 'seoDescription')) {
            $fromEntity = $this->entity->seoDescription();
            if ($fromEntity !== null && trim($fromEntity) !== '') {
                return trim($fromEntity);
            }
        }
        if ($fallback !== null && trim($fallback) !== '') {
            return trim($fallback);
        }
        if (! empty($settings['seo_default_description'])) {
            return (string) $settings['seo_default_description'];
        }

        return $site['site_motto'] ?: 'Official website of ' . $formalName;
    }

    /**
     * Canonical URL.
     *
     * Query strings are dropped, because every filterable index on this site
     * (?category=, ?department=, ?folder=) shows a subset of one canonical
     * listing. Pagination is the exception: page 2 of the news is its own
     * document and must not claim to be page 1.
     */
    private function canonical(): string
    {
        if (! empty($this->overrides['canonical'])) {
            return (string) $this->overrides['canonical'];
        }
        if ($this->entity && method_exists($this->entity, 'seoCanonical')) {
            if ($stored = $this->entity->seoCanonical()) {
                return $stored;
            }
        }

        $url = url()->current();
        $page = (int) request()->query('page', 1);

        return $page > 1 ? $url . '?page=' . $page : $url;
    }

    private function imageUrl(array $settings): ?string
    {
        if (! empty($this->overrides['image'])) {
            return municipal_upload_url((string) $this->overrides['image']);
        }
        if ($this->entity && method_exists($this->entity, 'seoImageUrl')) {
            if ($fromEntity = $this->entity->seoImageUrl()) {
                return $fromEntity;
            }
        }

        return municipal_upload_url($settings['seo_default_og_image'] ?? null);
    }

    private function ogType(): string
    {
        if (! empty($this->overrides['type'])) {
            return (string) $this->overrides['type'];
        }
        if ($this->entity instanceof NewsPost || $this->entity instanceof Notice) {
            return 'article';
        }
        if ($this->entity instanceof Event || $this->entity instanceof Meeting) {
            return 'event';
        }

        return 'website';
    }

    /**
     * Robots directive.
     *
     * The staging switch wins over everything: an operator who flips
     * "Discourage Search Engines" must get a noindex site even on records that
     * are individually fine to index.
     */
    private function robots(array $settings): string
    {
        if ($settings['seo_discourage'] === '1') {
            return 'noindex, nofollow';
        }
        // Product-level exclusions: token-protected resident pages, the search
        // results screen, and the two sensitive optional modules. These win
        // over any per-record setting because no operator should be able to
        // publish an individual's arrest record to Google by ticking a box.
        if ($this->routeIsAlwaysNoindex()) {
            return 'noindex, nofollow';
        }
        if (! empty($this->overrides['noindex'])) {
            return 'noindex, follow';
        }
        if ($this->entity && method_exists($this->entity, 'seoNoindex') && $this->entity->seoNoindex()) {
            return 'noindex, follow';
        }
        // A staff member previewing an unpublished record still gets a real
        // page, but it must never be indexed if a crawler somehow reaches it.
        if ($this->entity && method_exists($this->entity, 'seoIsPublic') && ! $this->entity->seoIsPublic()) {
            return 'noindex, follow';
        }

        return 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    }

    /** Does the current route match one of the always-noindex patterns? */
    public function routeIsAlwaysNoindex(): bool
    {
        $patterns = (array) config('municipal.seo.noindex_routes', []);

        return $patterns !== [] && request()->routeIs(...$patterns);
    }

    /* ------------------------------------------------------------------ */

    /** The page's JSON-LD graph, already encoded, or null when switched off. */
    private function jsonLd(array $settings, ?string $title, ?string $description, string $canonical, ?string $image): ?string
    {
        if ($settings['seo_structured_data'] !== '1') {
            return null;
        }

        $builder = new JsonLd;
        $nodes = $builder->organizationGraph();

        if ($this->entity) {
            if ($node = $builder->forEntity($this->entity, $description, $canonical, $image)) {
                $nodes[] = $node;
            }
        }

        $crumbs = $this->breadcrumbs ?: $this->derivedBreadcrumbs($title);
        if (count($crumbs) > 1) {
            $nodes[] = $builder->breadcrumbList($crumbs);
        }

        foreach ($this->extraSchema as $node) {
            $nodes[] = $node;
        }

        return $builder->encode($nodes);
    }

    /**
     * Breadcrumbs inferred from the record's type, so every detail page gets a
     * BreadcrumbList without each controller hand-writing one. Pages use their
     * real parent trail instead, since a CMS page can nest.
     */
    private function derivedBreadcrumbs(?string $title): array
    {
        if (! $this->entity) {
            return [];
        }

        $crumbs = [['label' => 'Home', 'url' => route('site.home')]];

        if ($this->entity instanceof Page) {
            foreach ($this->entity->trail() as $node) {
                $crumbs[] = ['label' => $node->title, 'url' => route('site.page', $node->slug)];
            }

            return $crumbs;
        }

        $section = self::SECTIONS[$this->entity::class] ?? null;
        if (! $section) {
            return [];
        }

        $crumbs[] = ['label' => $section[0], 'url' => route($section[1])];

        if ($url = $this->entity->seoUrl()) {
            $crumbs[] = ['label' => $title ?: $this->entity->seoTitle(), 'url' => $url];
        }

        return $crumbs;
    }
}
