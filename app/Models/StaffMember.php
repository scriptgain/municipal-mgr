<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StaffMember extends Model
{
    use Auditable;

    protected $fillable = [
        'department_id', 'name', 'job_title', 'email', 'phone', 'extension',
        'office', 'bio', 'photo_path', 'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'bool', 'sort_order' => 'int'];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** Avatar initials for the directory table/grid (house style). */
    public function initials(): string
    {
        return Str::of($this->name)->explode(' ')->filter()->take(2)
            ->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)))->implode('');
    }

    public function phoneDisplay(): string
    {
        return trim($this->phone . ($this->extension ? ' ext. ' . $this->extension : ''));
    }
}
