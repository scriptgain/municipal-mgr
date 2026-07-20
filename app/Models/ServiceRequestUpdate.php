<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequestUpdate extends Model
{
    protected $fillable = ['service_request_id', 'user_id', 'status', 'note', 'is_public'];

    protected function casts(): array
    {
        return ['is_public' => 'bool'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
