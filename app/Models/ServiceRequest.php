<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServiceRequest extends Model
{
    use Auditable;

    protected $fillable = [
        'reference', 'tracking_token', 'category', 'description', 'location_text',
        'latitude', 'longitude', 'photo_path', 'reporter_name', 'reporter_email',
        'reporter_phone', 'constituent_id', 'is_anonymous', 'status', 'priority', 'department_id',
        'assigned_to', 'acknowledged_at', 'resolved_at', 'ip',
    ];

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'bool',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $r) {
            $r->reference ??= self::nextReference();
            $r->tracking_token ??= Str::lower(Str::random(40));
        });
    }

    /** Human-quotable reference: SR-2026-000412. */
    public static function nextReference(): string
    {
        $year = now()->format('Y');
        $n = static::where('reference', 'like', "SR-{$year}-%")->count() + 1;

        return sprintf('SR-%s-%06d', $year, $n);
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereNotIn('status', ['resolved', 'closed', 'duplicate']);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

        return $q->where(fn (Builder $s) => $s
            ->where('reference', 'like', $like)
            ->orWhere('description', 'like', $like)
            ->orWhere('location_text', 'like', $like)
            ->orWhere('reporter_name', 'like', $like));
    }

    public function updatesLog(): HasMany
    {
        return $this->hasMany(ServiceRequestUpdate::class)->latest();
    }

    public function publicUpdates(): HasMany
    {
        return $this->hasMany(ServiceRequestUpdate::class)->where('is_public', true)->oldest();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** The resident record this request belongs to. Null when anonymous. */
    public function constituent(): BelongsTo
    {
        return $this->belongsTo(Constituent::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function statusLabel(): string
    {
        return config('municipal.request_statuses.' . $this->status . '.label', Str::headline($this->status));
    }

    public function statusColor(): string
    {
        return config('municipal.request_statuses.' . $this->status . '.color', 'neutral');
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['resolved', 'closed', 'duplicate'], true);
    }
}
