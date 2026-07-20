<?php

namespace App\Http\Middleware;

use App\Services\Payments\PaymentSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the Stripe webhook endpoint only.
 *
 * Deliberately checks isConfigured() rather than isEnabled(). If an operator
 * switches the module off while payments are still in flight, Stripe will keep
 * delivering events for those charges for some time, and those events still
 * need to settle against the bills they belong to. Refusing them would leave
 * residents charged with their bill still showing unpaid.
 *
 * On an install that never configured Stripe there is nothing to settle, so the
 * endpoint 404s like the rest of the module. Signature verification in the
 * controller is the real security boundary either way.
 */
class EnsurePaymentsConfigured
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(PaymentSettings::isConfigured(), 404);

        return $next($request);
    }
}
