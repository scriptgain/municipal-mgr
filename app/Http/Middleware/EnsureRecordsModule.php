<?php

namespace App\Http\Middleware;

use App\Services\RecordsSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the Jail And Arrest Records module.
 *
 * A 404, never a redirect and never a "this feature is disabled" page. A
 * redirect to some /records-disabled notice would announce that the town runs
 * a blotter and simply has it switched off, which is exactly the thing an
 * install that has not enabled the module should not be announcing.
 */
class EnsureRecordsModule
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(RecordsSettings::enabled(), 404);

        return $next($request);
    }
}
