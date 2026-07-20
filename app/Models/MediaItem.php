<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaItem extends Model
{
    use Auditable;

    protected $fillable = [
        'name', 'path', 'mime_type', 'size', 'width', 'height', 'alt_text', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['size' => 'int', 'width' => 'int', 'height' => 'int'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

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
}
