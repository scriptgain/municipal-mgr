<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * First-run guard: force a fresh install through the /admin/setup wizard until
 * an admin exists and a license has been dealt with.
 *
 * Critically, this ONLY guards the staff area. The public municipal site is
 * never redirected to a setup wizard — a half-configured install must still
 * serve whatever content exists rather than showing residents an admin screen.
 */
class EnsureSetup
{
    /** Staff paths that must stay reachable while setup is pending. */
    private array $allowPrefixes = [
        'admin/setup',
        'admin/login',
        'admin/logout',
        'admin/2fa',
        'brand',
        'up',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // The public site is out of scope entirely.
        if (! $request->is('admin', 'admin/*')) {
            return $next($request);
        }

        $needsSetup = Setting::get('setup_complete') !== '1';

        if ($needsSetup) {
            if (! $this->isAllowedWhilePending($request)) {
                return redirect()->route('setup.index');
            }

            return $next($request);
        }

        // Setup is done — never show the wizard again.
        if ($request->is('admin/setup', 'admin/setup/*')) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }

    private function isAllowedWhilePending(Request $request): bool
    {
        foreach ($this->allowPrefixes as $prefix) {
            if ($request->is($prefix, $prefix . '/*')) {
                return true;
            }
        }

        return false;
    }
}
