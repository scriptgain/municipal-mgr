<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use Auditable, HasSlug;

    protected $fillable = [
        'department_id', 'title', 'slug', 'category', 'description', 'starts_at',
        'ends_at', 'all_day', 'location', 'address', 'registration_url',
        'image_path', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'bool',
            'is_published' => 'bool',
        ];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at');
    }

    public function scopeInMonth(Builder $q, int $year, int $month): Builder
    {
        $start = now()->setDate($year, $month, 1)->startOfMonth();

        return $q->whereBetween('starts_at', [$start, $start->copy()->endOfMonth()]);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** "Aug 14, 2026, 6:00 PM to 8:00 PM" / "Aug 14, 2026 (All Day)". */
    public function whenDisplay(): string
    {
        $d = config('municipal.date_format', 'M j, Y');
        $t = config('municipal.time_format', 'g:i A');

        if ($this->all_day) {
            return $this->starts_at->format($d) . ' (All Day)';
        }

        $out = $this->starts_at->format($d . ', ' . $t);
        if ($this->ends_at) {
            $out .= ' to ' . ($this->ends_at->isSameDay($this->starts_at)
                ? $this->ends_at->format($t)
                : $this->ends_at->format($d . ', ' . $t));
        }

        return $out;
    }
}
