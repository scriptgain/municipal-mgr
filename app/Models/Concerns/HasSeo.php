<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Per-entity SEO fields plus the fallbacks that make them optional.
 *
 * The five stored columns (meta_title, meta_description, og_image,
 * canonical_url, noindex) are all nullable. When one is blank this trait
 * derives a value from the record's real content, so a municipality that never
 * opens the SEO panel still ships correct titles, descriptions, and social
 * cards. Nothing derived is ever written back to the database: an editor who
 * rewrites a story should get a new description for free, not a stale one.
 *
 * Models customise the derivation by overriding the seo*Sources() methods
 * rather than by redeclaring properties — PHP forbids a class from changing a
 * trait property's default value.
 */
trait HasSeo
{
    /** SEO columns every content model shares. Merged into $fillable. */
    public static array $seoFillable = [
        'meta_title', 'meta_description', 'og_image', 'canonical_url', 'noindex',
    ];

    /* ------------------------------------------------------------------ */
    /* Overridable sources                                                 */
    /* ------------------------------------------------------------------ */

    /**
     * Attributes searched, in order, for a description when meta_description
     * is blank. The first non-empty one wins.
     */
    protected function seoDescriptionSources(): array
    {
        return ['summary', 'excerpt', 'description', 'body'];
    }

    /** Attributes searched, in order, for a social image when og_image is blank. */
    protected function seoImageSources(): array
    {
        return ['og_image', 'image_path', 'hero_image_path'];
    }

    /** Named route for this record's public page, or null if it has none. */
    protected function seoRouteName(): ?string
    {
        return null;
    }

    /** Schema.org @type used when this record is rendered as structured data. */
    public function seoSchemaType(): ?string
    {
        return null;
    }

    /* ------------------------------------------------------------------ */
    /* Resolved values                                                     */
    /* ------------------------------------------------------------------ */

    /** The <title> text: the operator's override, else the record's own name. */
    public function seoTitle(): string
    {
        $stored = trim((string) ($this->meta_title ?? ''));

        return $stored !== '' ? $stored : $this->seoDefaultTitle();
    }

    /** The record's natural heading. Overridden where a title is assembled. */
    public function seoDefaultTitle(): string
    {
        return trim((string) ($this->title ?? $this->name ?? $this->reference ?? ''));
    }

    /** Meta description: the operator's override, else derived from content. */
    public function seoDescription(): ?string
    {
        $stored = trim((string) ($this->meta_description ?? ''));

        return $stored !== '' ? $stored : $this->seoDerivedDescription();
    }

    /**
     * Description derived from the record's content.
     *
     * Override this (not seoDescriptionFromSources) when a model needs to look
     * somewhere a plain attribute list cannot reach, such as a page builder's
     * section blocks.
     */
    public function seoDerivedDescription(): ?string
    {
        return $this->seoDescriptionFromSources();
    }

    /** First non-empty description source, cleaned and clipped to snippet length. */
    final public function seoDescriptionFromSources(): ?string
    {
        foreach ($this->seoDescriptionSources() as $attribute) {
            $value = $this->getAttribute($attribute);
            if (is_string($value) && trim($value) !== '') {
                return static::seoSnippet($value);
            }
        }

        return null;
    }

    /** Absolute URL of the social share image, or null to fall back site-wide. */
    public function seoImageUrl(): ?string
    {
        foreach ($this->seoImageSources() as $attribute) {
            $value = $this->getAttribute($attribute);
            if (is_string($value) && trim($value) !== '') {
                return municipal_upload_url($value);
            }
        }

        return null;
    }

    /** Operator-set canonical override. Blank means "use this page's own URL". */
    public function seoCanonical(): ?string
    {
        $stored = trim((string) ($this->canonical_url ?? ''));

        return $stored !== '' ? $stored : null;
    }

    /** This record's own public URL, when it has a route. */
    public function seoUrl(): ?string
    {
        $route = $this->seoRouteName();

        return $route ? route($route, $this->getRouteKey()) : null;
    }

    /** Operator asked search engines to skip this record. */
    public function seoNoindex(): bool
    {
        return (bool) ($this->noindex ?? false);
    }

    /** lastmod for the sitemap. Real timestamps only, never "now". */
    public function seoLastModified(): ?Carbon
    {
        return $this->updated_at ?? $this->created_at ?? null;
    }

    /**
     * Whether a search engine should be able to reach this record at all.
     *
     * Generic across the three publishing shapes in this codebase: the
     * Publishable trait (status + published_at), a plain is_published boolean,
     * and a bare status column.
     */
    public function seoIsPublic(): bool
    {
        if ($this->seoNoindex()) {
            return false;
        }
        if (method_exists($this, 'isPublished')) {
            return (bool) $this->isPublished();
        }
        if (array_key_exists('is_published', $this->getAttributes())) {
            return (bool) $this->is_published;
        }
        if (array_key_exists('status', $this->getAttributes())) {
            return $this->status === 'published';
        }

        return true;
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Turn stored body copy into a meta-description-sized snippet.
     *
     * Strips markup, decodes entities, and collapses whitespace before
     * clipping, so an HTML story body does not produce a description full of
     * tag soup and &nbsp;.
     *
     * The limit is 155 with a single-character ellipsis, giving a worst case of
     * 156 characters. That has to stay under SeoAudit::DESC_MAX, or every
     * auto-derived description would report itself as too long in SEO Health.
     */
    public static function seoSnippet(?string $value, int $chars = 155): string
    {
        $text = strip_tags((string) $value);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return Str::limit($text, $chars, '…');
    }
}
