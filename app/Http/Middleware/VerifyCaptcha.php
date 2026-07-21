<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\FormDefinition;
use App\Services\Captcha\CaptchaManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side spam verification on a protected POST route.
 *
 * Applied as captcha:{context}, e.g. captcha:login. The context names the
 * per-form toggle and the fail policy. For form-builder submissions the context
 * is resolved from the bound form ("Contact Us" gets its own toggle), so the
 * route can just say captcha:forms and the manager narrows it.
 *
 * The client result is never trusted here: this is the only place the token is
 * checked, and a secret key never appears in the request or the response.
 */
class VerifyCaptcha
{
    public function __construct(private readonly CaptchaManager $captcha)
    {
    }

    public function handle(Request $request, Closure $next, string $context = 'forms'): Response
    {
        // Narrow a generic "forms" context to the specific form's toggle.
        if ($context === 'forms') {
            $form = $request->route('formDefinition');
            if ($form instanceof FormDefinition) {
                $context = $this->captcha->contextForForm($form);
            }
        }

        $verdict = $this->captcha->verify($request, $context);

        if (! $verdict->allowed) {
            AuditLog::record('captcha_blocked', 'Spam protection blocked a ' . $context . ' submission (' . $verdict->layer . ').');

            return back()
                ->withInput($request->except([
                    'password', 'password_confirmation',
                    \App\Services\Captcha\HoneypotGuard::HONEYPOT,
                ]))
                ->withErrors(['captcha' => $verdict->message]);
        }

        // Allowed only because the provider was unreachable and this context is
        // set to fail open: let the resident through, but leave a trail.
        if ($verdict->failedOpen) {
            AuditLog::record('captcha_failopen', 'Spam provider unreachable; allowed a ' . $context . ' submission under the fail-open policy.');
        }

        return $next($request);
    }
}
