<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobPosting extends Model
{
    use Auditable, HasSlug;

    protected $fillable = [
        'department_id', 'title', 'slug', 'employment_type', 'salary_range',
        'description', 'requirements', 'apply_url', 'apply_email',
        'application_document_id', 'posted_on', 'closes_at',
        'is_open_until_filled', 'status',
    ];

    protected function casts(): array
    {
        return [
            'posted_on' => 'date',
            'closes_at' => 'datetime',
            'is_open_until_filled' => 'bool',
        ];
    }

    /** Accepting applications: published and either open-ended or not yet closed. */
    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('status', 'published')
            ->where(fn (Builder $s) => $s->where('is_open_until_filled', true)
                ->orWhereNull('closes_at')
                ->orWhere('closes_at', '>=', now()));
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function applicationForm(): BelongsTo
    {
        return $this->belongsTo(FileItem::class, 'application_document_id');
    }

    public function closesDisplay(): string
    {
        if ($this->is_open_until_filled) {
            return 'Open Until Filled';
        }

        return $this->closes_at
            ? $this->closes_at->format(config('municipal.date_format', 'M j, Y'))
            : 'No Closing Date';
    }
}
