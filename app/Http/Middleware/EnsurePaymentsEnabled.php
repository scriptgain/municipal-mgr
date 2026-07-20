<?php

namespace App\Http\Middleware;

use App\Services\Payments\PaymentSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the Pay Your Bill module.
 *
 * A 404, never a redirect to a "payments are switched off" page. An install
 * that has not configured Stripe should look like a site that simply does not
 * take payments online, not like one advertising a disabled payments endpoint
 * for somebody to come back and probe later.
 *
 * The gate is re-evaluated on every request rather than at boot, so clearing a
 * credential takes the public payment pages down immediately instead of leaving
 * a checkout up that would fail at the card form.
 */
class EnsurePaymentsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(PaymentSettings::isEnabled(), 404);

        return $next($request);
    }
}
