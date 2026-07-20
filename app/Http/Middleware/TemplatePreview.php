<?php

namespace App\Http\Middleware;

use App\Services\Templates\TemplateOverrideStore;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Preview an unsaved template on the real site, for one admin, for ten minutes.
 *
 * The draft is written to a per-user directory that is prepended to the view
 * finder AHEAD of the published override layer, so the previewing admin sees
 * the draft and every other visitor keeps seeing the live site. Nothing is
 * persisted to the template_overrides table until the admin actually saves.
 *
 * Gated on the admin role and on the session key, both, because rendering a
 * template is executing code.
 */
class TemplatePreview
{
    /** Session key holding the active preview descriptor. */
    public const SESSION_KEY = 'template_preview';

    public function __construct(private TemplateOverrideStore $store)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $preview = $request->session()->get(self::SESSION_KEY);

        if (is_array($preview) && $this->isUsable($request, $preview)) {
            $dir = $this->store->previewPath((int) $preview['user_id']);
            if (is_dir($dir)) {
                View::prependLocation($dir);
                $request->attributes->set('template_preview_view', $preview['view'] ?? null);
            }
        } elseif ($preview) {
            // Expired or no longer permitted: clear it rather than leave a
            // stale draft one session variable away from coming back.
            $request->session()->forget(self::SESSION_KEY);
        }

        return $next($request);
    }

    private function isUsable(Request $request, array $preview): bool
    {
        $user = $request->user();

        return $user
            && $user->isAdmin()
            && (int) ($preview['user_id'] ?? 0) === $user->id
            && isset($preview['expires_at'])
            && now()->timestamp < (int) $preview['expires_at'];
    }
}
