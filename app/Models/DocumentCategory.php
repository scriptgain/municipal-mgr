<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SUPERSEDED by the unified File Manager. Nothing in the application
 * reads this model any more; Folder does its job.
 *
 * It is kept, along with the `document_categories` table it maps to, purely as the
 * rollback path for the 2026_07_19_1300xx migration series. Once an
 * operator is satisfied the new library is correct, this model and its
 * table can be dropped in a separate, deliberate migration.
 *
 * @deprecated Use App\Models\Folder instead.
 */
class DocumentCategory extends Model
{
    use Auditable, HasSlug;

    protected $fillable = ['name', 'slug', 'description', 'icon', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'int'];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
