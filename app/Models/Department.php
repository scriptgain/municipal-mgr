<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use Auditable, HasSeo, HasSlug;

    protected $fillable = [
        'name', 'slug', 'icon', 'summary', 'description', 'phone', 'fax', 'email',
        'address', 'hours', 'head_staff_id', 'sort_order', 'is_published',
        'meta_title', 'meta_description', 'og_image', 'canonical_url', 'noindex',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'bool', 'sort_order' => 'int', 'noindex' => 'bool'];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('name');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(StaffMember::class)->orderBy('sort_order');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'head_staff_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(FileItem::class);
    }

    public function news(): HasMany
    {
        return $this->hasMany(NewsPost::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /* ------------------------------------------------------------------ */
    /* SEO                                                                 */
    /* ------------------------------------------------------------------ */

    protected function seoRouteName(): ?string
    {
        return 'site.departments.show';
    }

    /**
     * A department page is what a resident lands on when they search for the
     * service it provides ("water bill", "building permit"), so it is marked
     * up as a GovernmentService rather than a generic page.
     */
    public function seoSchemaType(): ?string
    {
        return 'GovernmentService';
    }

    protected function seoDescriptionSources(): array
    {
        return ['summary', 'description'];
    }
}
