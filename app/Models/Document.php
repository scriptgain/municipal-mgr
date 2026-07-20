<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SUPERSEDED by the unified File Manager. Nothing in the application
 * reads this model any more; FileItem does its job.
 *
 * It is kept, along with the `documents` table it maps to, purely as the
 * rollback path for the 2026_07_19_1300xx migration series. Once an
 * operator is satisfied the new library is correct, this model and its
 * table can be dropped in a separate, deliberate migration.
 *
 * @deprecated Use App\Models\FileItem instead.
 */
class Document extends Model
{
    use Auditable, HasSlug;

    protected $fillable = [
        'document_category_id', 'department_id', 'title', 'slug', 'description',
        'keywords', 'reference', 'file_path', 'file_name', 'mime_type',
        'file_size', 'document_date', 'download_count', 'is_published', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'is_published' => 'bool',
            'file_size' => 'int',
            'download_count' => 'int',
        ];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    /**
     * Keyword search across the fields a resident would actually type.
     * Deliberately LIKE-based: it behaves identically on MySQL and SQLite, and
     * a document library of a few thousand rows never needs more.
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
            ->orWhere('reference', 'like', $like));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function sizeDisplay(): string
    {
        $b = (int) $this->file_size;
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
}
