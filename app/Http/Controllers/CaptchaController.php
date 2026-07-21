<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\Captcha\CaptchaManager;
use App\Services\Captcha\CaptchaSettings;
use Illuminate\Http\Request;

/**
 * Spam Protection settings: choose the active provider, enter its keys, tune the
 * v3 score threshold and the fail policy, and set the per-form toggles.
 *
 * Keys live in the DB Setting store, never .env (fleet rule). Secret keys are
 * write-only from here: they are saved but never rendered back into the page.
 */
class CaptchaController extends Controller
{
    public function __construct(private readonly CaptchaManager $captcha)
    {
    }

    public function edit()
    {
        abort_unless(auth()->user()->isEditor(), 403);

        return view('settings.captcha', [
            'providers' => $this->captcha->providers(),
            'active' => CaptchaSettings::activeProviderKey(),
            'contexts' => CaptchaManager::CONTEXTS,
            'get' => fn (string $key) => CaptchaSettings::get($key),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->isEditor(), 403);

        $providerKeys = array_keys($this->captcha->providers());

        $data = $request->validate([
            'captcha_provider' => ['required', 'in:' . implode(',', $providerKeys)],
            'captcha_min_seconds' => ['required', 'integer', 'min:0', 'max:60'],
            'captcha_v3_threshold' => ['required', 'numeric', 'min:0', 'max:1'],
            'captcha_fail_login' => ['required', 'in:open,closed'],
            'captcha_fail_public' => ['required', 'in:open,closed'],
            'captcha_builtin_mode' => ['required', 'in:arithmetic,word'],
            'captcha_recaptcha_v2_site' => ['nullable', 'string', 'max:255'],
            'captcha_recaptcha_v2_secret' => ['nullable', 'string', 'max:255'],
            'captcha_recaptcha_v3_site' => ['nullable', 'string', 'max:255'],
            'captcha_recaptcha_v3_secret' => ['nullable', 'string', 'max:255'],
            'captcha_hcaptcha_site' => ['nullable', 'string', 'max:255'],
            'captcha_hcaptcha_secret' => ['nullable', 'string', 'max:255'],
            'captcha_turnstile_site' => ['nullable', 'string', 'max:255'],
            'captcha_turnstile_secret' => ['nullable', 'string', 'max:255'],
        ]);

        // Per-context toggles arrive as 1/0 from the toggle switches.
        foreach (CaptchaManager::CONTEXTS as $context) {
            $data['captcha_on_' . $context] = $request->boolean('captcha_on_' . $context) ? '1' : '0';
        }

        CaptchaSettings::put($data);
        AuditLog::record('updated', 'Spam protection settings updated (provider: ' . $data['captcha_provider'] . ').');

        return redirect()->route('settings.captcha.edit')->with('status', 'Spam Protection Settings Saved.');
    }

    /** Real round-trip against the currently SAVED active provider. */
    public function test()
    {
        abort_unless(auth()->user()->isEditor(), 403);

        $provider = $this->captcha->active();
        $result = $provider->selfTest();

        $prefix = $result->passed ? 'Test Passed' : ($result->reachable ? 'Test Failed' : 'Service Unreachable');

        return back()->with('status', $prefix . ': ' . ($result->message ?: $provider->label()));
    }
}
