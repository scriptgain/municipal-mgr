<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    protected $fillable = ['form_definition_id', 'data', 'constituent_id', 'ip', 'read_at'];

    protected function casts(): array
    {
        return ['data' => 'array', 'read_at' => 'datetime'];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class, 'form_definition_id');
    }

    /** The resident record this submission belongs to, when one was resolvable. */
    public function constituent(): BelongsTo
    {
        return $this->belongsTo(Constituent::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
