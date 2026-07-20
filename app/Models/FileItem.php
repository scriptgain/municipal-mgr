<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single file in the unified file manager.
 *
 * This model subsumes BOTH of the systems that came before it: the public
 * Document library (ordinances, budgets, forms — slugged, counted downloads,
 * searchable) and the flat MediaItem library (images with alt text used across
 * the site). One table means one uploader, one permission model, and one place
 * staff go looking for a file.
 *
 * Named FileItem rather than File on purpose: `File` collides with Laravel's
 * File facade and with SplFileInfo in any class that also does uploads.
 */
class FileItem extends Model
{
    use Auditable, HasSeo, HasSlug;

    protected $table = 'files';

    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_STAFF = 'staff';

    public const KIND_IMAGE = 'image';
    public const KIND_DOCUMENT = 'document';
    public const KIND_OTHER = 'other';

    protected $fillable = [
        'folder_id', 'department_id', 'title', 'slug', 'description', 'keywords',
        'reference', 'path', 'file_name', 'mime_type', 'size', 'width', 'height',
        'alt_text', 'document_date', 'download_count', 'is_published', 'visibility',
        'kind', 'uploaded_by',
        'meta_title', 'meta_description', 'og_image', 'canonical_url', 'noindex',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'is_published' => 'bool',
            'size' => 'int',
            'width' => 'int',
            'height' => 'int',
            'download_count' => 'int',
            'noindex' => 'bool',
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Scopes                                                              */
    /* ------------------------------------------------------------------ */

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    /** Published AND publicly visible. What a resident is allowed to see. */
    public function scopePubliclyVisible(Builder $q): Builder
    {
        return $q->where('is_published', true)->where('visibility', self::VISIBILITY_PUBLIC);
    }

    public function scopeImages(Builder $q): Builder
    {
        return $q->where('kind', self::KIND_IMAGE);
    }

    public function scopeKind(Builder $q, ?string $kind): Builder
    {
        return $kind ? $q->where('kind', $kind) : $q;
    }

    public function scopeVisibility(Builder $q, ?string $visibility): Builder
    {
        return $visibility ? $q->where('visibility', $visibility) : $q;
    }

    /** Files directly inside a folder, or unfiled when null is passed. */
    public function scopeInFolder(Builder $q, ?int $folderId): Builder
    {
        return $folderId ? $q->where('folder_id', $folderId) : $q->whereNull('folder_id');
    }

    /**
     * Keyword search across the fields a resident would actually type.
     * Deliberately LIKE-based: it behaves identically on MySQL and SQLite, and
     * a municipal file library of a few thousand rows never needs more.
     */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

        return $q->where(fn (Builder $s) => $s
            ->where('title', 'like', $like)
            ->orWhere('description', 'like', $like)
            ->orWhere('keywords', 'like', $like)
            ->orWhere('reference', 'like', $like)
            ->orWhere('file_name', 'like', $like));
    }

    /* ------------------------------------------------------------------ */
    /* Relations                                                           */
    /* ------------------------------------------------------------------ */

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /* ------------------------------------------------------------------ */
    /* Presentation                                                        */
    /* ------------------------------------------------------------------ */

    public function isImage(): bool
    {
        return $this->kind === self::KIND_IMAGE;
    }

    public function isPublic(): bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }

    /** Direct URL to the stored file. Use for images; documents go via download(). */
    public function url(): string
    {
        return (string) municipal_upload_url($this->path);
    }

    public function sizeDisplay(): string
    {
        $b = (int) $this->size;
        foreach (['B', 'KB', 'MB', 'GB'] as $i => $unit) {
            if ($b < 1024 || $unit === 'GB') {
                return round($b, $i ? 1 : 0) . ' ' . $unit;
            }
            $b /= 1024;
        }

        return $b . ' B';
    }

    /** Short uppercase file kind for the badge column (PDF, DOCX, XLSX...). */
    public function extension(): string
    {
        return strtoupper(pathinfo((string) $this->file_name, PATHINFO_EXTENSION) ?: 'FILE');
    }

    /**
     * Classify a mime type into the three buckets the UI filters on.
     * Kept as a stored column rather than computed per row so the admin
     * "Type" filter is an indexed WHERE instead of a full scan.
     */
    public static function kindFor(?string $mime, ?string $fileName = null): string
    {
        $mime = strtolower((string) $mime);
        if (str_starts_with($mime, 'image/')) {
            return self::KIND_IMAGE;
        }

        $documentMimes = ['application/pdf', 'application/msword', 'application/rtf', 'text/plain', 'text/csv'];
        if (in_array($mime, $documentMimes, true)
            || str_contains($mime, 'officedocument')
            || str_contains($mime, 'opendocument')
            || str_contains($mime, 'ms-excel')
            || str_contains($mime, 'ms-powerpoint')) {
            return self::KIND_DOCUMENT;
        }

        // Fall back to the extension when the browser sent a useless mime type
        // (octet-stream is common for .docx from some clients).
        $ext = strtolower(pathinfo((string) $fileName, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'], true)) {
            return self::KIND_IMAGE;
        }
        if (in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt', 'rtf', 'odt', 'ods'], true)) {
            return self::KIND_DOCUMENT;
        }

        return self::KIND_OTHER;
    }

    /* ------------------------------------------------------------------ */
    /* SEO                                                                 */
    /* ------------------------------------------------------------------ */

    protected function seoRouteName(): ?string
    {
        return 'site.files.show';
    }

    public function seoSchemaType(): ?string
    {
        return 'WebPage';
    }

    protected function seoDescriptionSources(): array
    {
        return ['description', 'keywords'];
    }

    /** An image file is its own best social card. */
    protected function seoImageSources(): array
    {
        return $this->isImage() ? ['og_image', 'path'] : ['og_image'];
    }

    /**
     * Staff-only files must never reach a sitemap or carry an indexable meta
     * block, so visibility is checked on top of the published flag.
     */
    public function seoIsPublic(): bool
    {
        return ! $this->seoNoindex() && $this->is_published && $this->isPublic();
    }
}
