<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One point-in-time snapshot of a template, written on every save. */
class TemplateVersion extends Model
{
    protected $fillable = ['view', 'content', 'action', 'note', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return [
            'save' => 'Saved',
            'revert' => 'Reverted',
            'reset' => 'Reset To Default',
            'import' => 'Imported',
        ][$this->action] ?? 'Saved';
    }
}
