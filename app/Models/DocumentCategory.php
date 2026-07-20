<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
