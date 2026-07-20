<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meeting extends Model
{
    use Auditable, HasSlug;

    protected $fillable = [
        'body', 'title', 'slug', 'meets_at', 'location', 'address', 'summary',
        'agenda_document_id', 'minutes_document_id', 'packet_document_id',
        'video_url', 'status', 'is_published',
    ];

    protected function casts(): array
    {
        return ['meets_at' => 'datetime', 'is_published' => 'bool'];
    }

    protected function slugSource(): string
    {
        return trim($this->body . ' ' . ($this->title ?: 'Meeting') . ' ' . ($this->meets_at?->format('Y-m-d') ?? ''));
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('meets_at', '>=', now()->startOfDay())->orderBy('meets_at');
    }

    public function scopePast(Builder $q): Builder
    {
        return $q->where('meets_at', '<', now()->startOfDay())->orderByDesc('meets_at');
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'agenda_document_id');
    }

    public function minutes(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'minutes_document_id');
    }

    public function packet(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'packet_document_id');
    }

    public function displayTitle(): string
    {
        return trim($this->body . ' — ' . ($this->title ?: 'Regular Meeting'), ' —');
    }
}
