<?php

namespace App\Services\Seo;

use App\Models\Bid;
use App\Models\Department;
use App\Models\Event;
use App\Models\JobPosting;
use App\Models\Meeting;
use App\Models\NewsPost;
use App\Models\Notice;
use App\Services\SiteSettings;
use Illuminate\Database\Eloquent\Model;

/**
 * Structured data for the public site.
 *
 * This is where a civic site earns rich results: agendas, job openings, and
 * council meetings all have first-class schema.org types, and search engines
 * surface them far better marked up than as plain pages.
 *
 * Every node is built from real record data. Nothing here invents a value it
 * cannot source, because fabricated structured data is a manual-action risk,
 * not just noise.
 */
class JsonLd
{
    /**
     * The two nodes that belong on every page: who the municipality is, and
     * the site itself with its search endpoint.
     */
    public function organizationGraph(): array
    {
        $site = SiteSettings::all();
        $settings = SeoSettings::all();
        $name = SiteSettings::formalName();
        $home = route('site.home');

        $organization = array_filter([
            '@type' => $settings['seo_organization_type'] ?: 'GovernmentOrganization',
            '@id' => $home . '#organization',
            'name' => $name,
            'alternateName' => $site['site_name'] !== $name ? $site['site_name'] : null,
            'url' => $home,
            'logo' => municipal_upload_url($site['site_seal_path'] ?: $site['site_logo_path']),
            'image' => municipal_upload_url($site['site_seal_path'] ?: $site['site_logo_path']),
            'slogan' => $site['site_motto'],
            'email' => $site['contact_email'],
            'telephone' => $site['contact_phone'],
            'faxNumber' => $site['contact_fax'],
            'address' => $this->postalAddress($site),
            'areaServed' => $site['site_name'] ? ['@type' => 'AdministrativeArea', 'name' => $site['site_name']] : null,
            'sameAs' => $this->sameAs($site),
        ], fn ($v) => $v !== null && $v !== [] && $v !== '');

        $website = array_filter([
            '@type' => 'WebSite',
            '@id' => $home . '#website',
            'url' => $home,
            'name' => $name,
            'publisher' => ['@id' => $home . '#organization'],
            'inLanguage' => 'en-US',
            // Lets a search engine offer a site-search box straight in results.
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('site.search') . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ], fn ($v) => $v !== null && $v !== '');

        return [$organization, $website];
    }

    /** The node describing the record this page is about, if it has a type. */
    public function forEntity(Model $entity, ?string $description, string $canonical, ?string $image): ?array
    {
        return match (true) {
            $entity instanceof NewsPost => $this->newsArticle($entity, $description, $canonical, $image),
            $entity instanceof Event => $this->event($entity, $description, $canonical, $image),
            $entity instanceof Meeting => $this->meeting($entity, $description, $canonical),
            $entity instanceof JobPosting => $this->jobPosting($entity, $description, $canonical),
            $entity instanceof Department => $this->governmentService($entity, $description, $canonical),
            $entity instanceof Notice => $this->article($entity, $description, $canonical),
            $entity instanceof Bid => $this->webPage($entity, $description, $canonical),
            default => null,
        };
    }

    public function breadcrumbList(array $crumbs): array
    {
        $items = [];
        $position = 1;

        foreach ($crumbs as $crumb) {
            $label = trim((string) ($crumb['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $items[] = array_filter([
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $label,
                'item' => $crumb['url'] ?? $crumb['href'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Encode a graph for a <script type="application/ld+json"> block.
     *
     * Slashes stay unescaped so URLs are readable, and any literal "</" is
     * broken up: without that a description containing "</script>" would end
     * the script element early and inject markup into the page.
     */
    public function encode(array $nodes): ?string
    {
        $nodes = array_values(array_filter($nodes));
        if (! $nodes) {
            return null;
        }

        $json = json_encode(
            ['@context' => 'https://schema.org', '@graph' => $nodes],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        if ($json === false) {
            return null;
        }

        return str_replace(['</', '<!--'], ['<\/', '<!--'], $json);
    }

    /* ------------------------------------------------------------------ */
    /* Entity nodes                                                        */
    /* ------------------------------------------------------------------ */

    private function newsArticle(NewsPost $post, ?string $description, string $url, ?string $image): array
    {
        return array_filter([
            '@type' => 'NewsArticle',
            '@id' => $url . '#article',
            'headline' => $post->seoTitle(),
            'description' => $description,
            'image' => $image,
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => ($post->updated_at ?? $post->published_at)?->toAtomString(),
            'articleSection' => $post->category,
            'author' => $this->author($post),
            'publisher' => $this->publisherRef(),
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'isAccessibleForFree' => true,
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function article(Notice $notice, ?string $description, string $url): array
    {
        return array_filter([
            '@type' => 'Article',
            '@id' => $url . '#article',
            'headline' => $notice->seoTitle(),
            'description' => $description,
            'datePublished' => $notice->posted_at?->toAtomString(),
            'dateModified' => $notice->updated_at?->toAtomString(),
            'expires' => $notice->expires_at?->toAtomString(),
            'articleSection' => $notice->notice_type,
            'author' => $this->publisherRef(),
            'publisher' => $this->publisherRef(),
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function event(Event $event, ?string $description, string $url, ?string $image): array
    {
        return array_filter([
            '@type' => 'Event',
            '@id' => $url . '#event',
            'name' => $event->seoTitle(),
            'description' => $description,
            'image' => $image,
            'startDate' => $this->eventDate($event->starts_at, $event->all_day),
            'endDate' => $this->eventDate($event->ends_at, $event->all_day),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => $this->place($event->location, $event->address),
            'organizer' => $this->publisherRef(),
            'url' => $url,
            'isAccessibleForFree' => true,
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function meeting(Meeting $meeting, ?string $description, string $url): array
    {
        return array_filter([
            '@type' => 'Event',
            '@id' => $url . '#event',
            'name' => $meeting->displayTitle(),
            'description' => $description,
            'startDate' => $meeting->meets_at?->toAtomString(),
            'eventStatus' => $meeting->status === 'cancelled'
                ? 'https://schema.org/EventCancelled'
                : 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => $meeting->video_url
                ? 'https://schema.org/MixedEventAttendanceMode'
                : 'https://schema.org/OfflineEventAttendanceMode',
            'location' => $this->place($meeting->location, $meeting->address),
            'organizer' => $this->publisherRef(),
            'url' => $url,
            'isAccessibleForFree' => true,
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function jobPosting(JobPosting $job, ?string $description, string $url): array
    {
        $site = SiteSettings::all();

        return array_filter([
            '@type' => 'JobPosting',
            '@id' => $url . '#job',
            'title' => $job->seoDefaultTitle(),
            'description' => $description,
            'datePosted' => $job->posted_on?->toDateString(),
            // Google ignores validThrough on an open-until-filled posting, and
            // sending a made-up date would be worse than sending none.
            'validThrough' => $job->is_open_until_filled ? null : $job->closes_at?->toAtomString(),
            'employmentType' => $this->employmentType($job->employment_type),
            'hiringOrganization' => $this->publisherRef(),
            'jobLocation' => $this->place(
                $job->department?->name,
                $site['contact_address'] ? trim($site['contact_address'] . ', ' . $site['contact_city_state_zip']) : null
            ),
            'employerOverview' => $job->department?->summary,
            'url' => $url,
            'directApply' => (bool) $job->apply_url,
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function governmentService(Department $department, ?string $description, string $url): array
    {
        $site = SiteSettings::all();

        return array_filter([
            '@type' => 'GovernmentService',
            '@id' => $url . '#service',
            'name' => $department->seoDefaultTitle(),
            'description' => $description,
            'serviceType' => $department->name,
            'provider' => $this->publisherRef(),
            'areaServed' => $site['site_name']
                ? ['@type' => 'AdministrativeArea', 'name' => $site['site_name']]
                : null,
            'availableChannel' => array_filter([
                '@type' => 'ServiceChannel',
                'serviceUrl' => $url,
                'servicePhone' => $department->phone,
                'serviceLocation' => $this->place($department->name, $department->address),
            ], fn ($v) => $v !== null && $v !== ''),
            'url' => $url,
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function webPage(Model $entity, ?string $description, string $url): array
    {
        return array_filter([
            '@type' => 'WebPage',
            '@id' => $url . '#webpage',
            'name' => $entity->seoTitle(),
            'description' => $description,
            'url' => $url,
            'isPartOf' => ['@id' => route('site.home') . '#website'],
            'publisher' => $this->publisherRef(),
        ], fn ($v) => $v !== null && $v !== '');
    }

    /* ------------------------------------------------------------------ */
    /* Fragments                                                           */
    /* ------------------------------------------------------------------ */

    private function publisherRef(): array
    {
        return ['@id' => route('site.home') . '#organization'];
    }

    private function author(NewsPost $post): array
    {
        // A named staff author when there is one; otherwise the municipality
        // itself is the author, which is true of most civic announcements.
        return $post->author?->name
            ? ['@type' => 'Person', 'name' => $post->author->name]
            : $this->publisherRef();
    }

    private function place(?string $name, ?string $address): ?array
    {
        $name = trim((string) $name);
        $address = trim((string) $address);
        if ($name === '' && $address === '') {
            return null;
        }

        return array_filter([
            '@type' => 'Place',
            'name' => $name ?: null,
            'address' => $address ?: null,
        ], fn ($v) => $v !== null);
    }

    private function postalAddress(array $site): ?array
    {
        if (empty($site['contact_address']) && empty($site['contact_city_state_zip'])) {
            return null;
        }

        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $site['contact_address'],
            'addressLocality' => $site['contact_city_state_zip'],
            'addressRegion' => $site['site_state'],
            'addressCountry' => 'US',
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function sameAs(array $site): array
    {
        return array_values(array_filter([
            $site['social_facebook'] ?? null,
            $site['social_x'] ?? null,
            $site['social_youtube'] ?? null,
            $site['social_instagram'] ?? null,
            $site['social_nextdoor'] ?? null,
        ]));
    }

    /** An all-day event is a date, not a moment, in schema.org terms. */
    private function eventDate($value, bool $allDay): ?string
    {
        if (! $value) {
            return null;
        }

        return $allDay ? $value->toDateString() : $value->toAtomString();
    }

    /** Map the stored label onto the schema.org employment type vocabulary. */
    private function employmentType(?string $value): ?string
    {
        $key = strtoupper(str_replace([' ', '-'], '_', trim((string) $value)));

        return match ($key) {
            'FULL_TIME', 'FULLTIME' => 'FULL_TIME',
            'PART_TIME', 'PARTTIME' => 'PART_TIME',
            'CONTRACT', 'CONTRACTOR' => 'CONTRACTOR',
            'TEMPORARY', 'SEASONAL' => 'TEMPORARY',
            'INTERN', 'INTERNSHIP' => 'INTERN',
            'VOLUNTEER' => 'VOLUNTEER',
            'PER_DIEM' => 'PER_DIEM',
            default => null,
        };
    }
}
