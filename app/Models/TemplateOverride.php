<?php

namespace App\Models;

use App\Services\Templates\TemplateOverrideStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A municipality's replacement for one shipped Blade template.
 *
 * The row is the source of truth. The file the view finder actually reads is a
 * derived cache under storage/app/template-overrides, kept in step by the
 * model events below so no caller can ever save an override and forget to
 * materialise it.
 */
class TemplateOverride extends Model
{
    protected $fillable = ['view', 'content', 'updated_by'];

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function versions()
    {
        return TemplateVersion::where('view', $this->view)->latest('id');
    }

    protected static function booted(): void
    {
        static::saved(function (self $override) {
            app(TemplateOverrideStore::class)->write($override->view, $override->content);
        });

        static::deleted(function (self $override) {
            app(TemplateOverrideStore::class)->forget($override->view);
        });
    }
}
