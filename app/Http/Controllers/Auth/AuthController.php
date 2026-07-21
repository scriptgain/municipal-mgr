<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Staff demo personas offered as one-click logins from the allowlisted IP.
     *
     * Residents never log in to a municipal site, so every persona is a STAFF
     * role. Each entry is presentation only; the account it resolves to is
     * looked up in resolvePersona() so the picker degrades gracefully when a
     * given demo user has not been seeded.
     */
    public const DEMO_PERSONAS = [
        'admin' => [
            'label' => 'Administrator',
            'description' => 'Full Access To Every Panel And Setting.',
            'icon' => 'shield',
        ],
        'editor' => [
            'label' => 'Site Editor',
            'description' => 'Edits Content Across Every Department.',
            'icon' => 'edit',
        ],
        'department_editor' => [
            'label' => 'Department Editor',
            'description' => 'Scoped To Parks And Recreation Only.',
            'icon' => 'building',
        ],
        'viewer' => [
            'label' => 'Viewer (Read Only)',
            'description' => 'Read Only. Denied The Resident PII Pages.',
            'icon' => 'lock',
        ],
    ];

    public function show(Request $request)
    {
        return view('auth.login', [
            'demoPersonas' => self::demoPersonas($request),
        ]);
    }

    /**
     * One-click staff sign-in, restricted to an allowlisted IP.
     *
     * Both the buttons and the endpoint use demoLoginAllowed(), so the route is
     * not merely hidden: a POST from any other address 404s. Inert unless
     * dev_login_ip is set in Settings, so the whole picker can be switched off
     * from the admin without a deploy.
     *
     * This only resolves to a real client IP because trustProxies now includes
     * the Cloudflare ranges. Under the old loopback-only list every request
     * looked like a Cloudflare edge address and this would match nobody.
     */
    public static function demoLoginAllowed(Request $request): bool
    {
        $allowed = trim((string) \App\Models\Setting::get('dev_login_ip', ''));

        if ($allowed === '') {
            return false;
        }

        $ips = array_filter(array_map('trim', explode(',', $allowed)));

        return in_array((string) $request->ip(), $ips, true);
    }

    /** The demo persona each key maps to, resolved by email with role fallbacks. */
    public static function resolvePersona(string $key): ?\App\Models\User
    {
        if (! array_key_exists($key, self::DEMO_PERSONAS)) {
            return null;
        }

        $email = match ($key) {
            'admin' => 'clerk@cottonwoodsprings.example.gov',
            'editor' => 'demo-editor@cottonwoodsprings.example.gov',
            'department_editor' => 'parks-editor@cottonwoodsprings.example.gov',
            'viewer' => 'demo-viewer@cottonwoodsprings.example.gov',
            default => '',
        };

        if ($email !== '' && $user = \App\Models\User::where('email', $email)->first()) {
            return $user;
        }

        // The admin persona also honours the legacy dev_login_email setting, so
        // an install that only ever configured that one account still works.
        if ($key === 'admin') {
            $devEmail = trim((string) \App\Models\Setting::get('dev_login_email', ''));
            if ($devEmail !== '' && $user = \App\Models\User::where('email', $devEmail)->first()) {
                return $user;
            }
        }

        // Last resort: any account holding the role, so the picker is never
        // silently missing a persona the install actually has.
        $role = $key === 'admin' ? 'admin' : $key;

        return \App\Models\User::where('role', $role)->orderByDesc('is_active')->first();
    }

    /**
     * The personas to render on the login screen. Empty (nothing shown) unless
     * the request comes from the allowlisted IP.
     */
    public static function demoPersonas(Request $request): array
    {
        if (! self::demoLoginAllowed($request)) {
            return [];
        }

        $out = [];
        foreach (self::DEMO_PERSONAS as $key => $meta) {
            if ($user = self::resolvePersona($key)) {
                $out[] = $meta + ['key' => $key, 'name' => $user->name];
            }
        }

        return $out;
    }

    public function devLogin(Request $request, string $persona = 'admin')
    {
        abort_unless(self::demoLoginAllowed($request), 404);

        $user = self::resolvePersona($persona);

        abort_if($user === null, 404);

        Auth::login($user);
        $request->session()->regenerate();
        \App\Models\AuditLog::record('login', 'Signed in via demo persona ['.$persona.'] quick login from '.$request->ip());

        return redirect()->intended(route('dashboard'));
    }


    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Per-account lockout: 5 failed tries for an email, then a 60s cooldown.
        // Keyed by email only — the origin sits behind Cloudflare, so client IPs
        // rotate per edge and can't be used to accumulate attempts reliably.
        $key = 'login:'.strtolower($credentials['email']);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            \App\Models\AuditLog::record('login_blocked', 'Login throttled for '.$credentials['email']);
            throw ValidationException::withMessages([
                'email' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($key);
            // Firewall: record the success and reset this IP's failed-attempt counter.
            if ($ip = $request->ip()) {
                \App\Models\LoginAttempt::where('ip', $ip)->where('successful', false)->delete();
                \App\Models\LoginAttempt::create(['ip' => $ip, 'email' => $credentials['email'], 'successful' => true]);
            }
            $user = Auth::user();
            if ($user->hasTwoFactor()) {
                // Skip the code prompt if this device was remembered (within 30 days).
                if (hash_equals($this->deviceToken($user), (string) $request->cookie('td_2fa'))) {
                    $request->session()->regenerate();
                    \App\Models\AuditLog::record('login', 'Signed in (2FA remembered device)');

                    return redirect()->intended(route('dashboard'));
                }
                Auth::logout();
                $request->session()->put('2fa:user', $user->id);
                $request->session()->put('2fa:remember', $request->boolean('remember'));

                return redirect()->route('2fa.challenge');
            }
            $request->session()->regenerate();
            \App\Models\AuditLog::record('login', 'Signed in');

            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($key, 60);

        // Firewall: log the failed attempt and auto-ban the IP if it crosses the
        // configured limit within the lockout window. Allowlisted IPs are spared.
        if ($ip = $request->ip()) {
            \App\Models\LoginAttempt::create([
                'ip' => $ip,
                'email' => $credentials['email'],
                'successful' => false,
            ]);

            $limit = (int) \App\Models\Setting::get('failed_login_limit', 10);
            $window = (int) \App\Models\Setting::get('lockout_minutes', 60);
            if ($limit > 0 && $window > 0
                && ! \App\Support\Firewall::ipAllowed($ip, \App\Support\Firewall::allowlist())) {
                $recentFails = \App\Models\LoginAttempt::where('ip', $ip)
                    ->where('successful', false)
                    ->where('created_at', '>=', now()->subMinutes($window))
                    ->count();
                if ($recentFails >= $limit) {
                    \App\Models\BannedIp::updateOrCreate(
                        ['ip' => $ip],
                        [
                            'reason' => 'Auto-ban: '.$recentFails.' failed login attempts',
                            'expires_at' => now()->addMinutes($window),
                        ],
                    );
                    \App\Models\AuditLog::record('firewall_autoban', 'Auto-banned IP '.$ip.' after '.$recentFails.' failed logins');
                }
            }
        }

        return back()
            ->withErrors(['email' => 'Those credentials do not match our records.'])
            ->onlyInput('email');
    }

    /** Show the 2FA code prompt after a valid password. */
    public function challenge(Request $request)
    {
        if (! $request->session()->has('2fa:user')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function challengeVerify(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);
        $id = $request->session()->get('2fa:user');
        if (! $id) {
            return redirect()->route('login');
        }
        $user = \App\Models\User::find($id);
        if (! $user || ! \App\Support\Totp::verify((string) $user->two_factor_secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'That code is incorrect.']);
        }
        Auth::loginUsingId($id, (bool) $request->session()->get('2fa:remember', false));
        $request->session()->forget(['2fa:user', '2fa:remember']);
        $request->session()->regenerate();
        \App\Models\AuditLog::record('login', 'Signed in (2FA)');

        $response = redirect()->intended(route('dashboard'));
        if ($request->boolean('remember_device')) {
            // 30-day encrypted cookie; the token is derived from the 2FA secret so
            // resetting 2FA invalidates every remembered device.
            $response->withCookie(cookie('td_2fa', $this->deviceToken($user), 43200));
        }

        return $response;
    }

    /** Per-user, per-secret token used to remember a 2FA-verified device. */
    private function deviceToken(\App\Models\User $user): string
    {
        return hash_hmac('sha256', $user->id.'|'.$user->two_factor_secret, (string) config('app.key'));
    }

    /**
     * One-click login via a short-lived signed URL. The 'signed' middleware
     * rejects any tampered or expired link, so the signature is the credential;
     * no password is transmitted. Convenience for the admin — skips 2FA.
     */
    public function magic(Request $request, \App\Models\User $user)
    {
        Auth::login($user);
        $request->session()->regenerate();
        \App\Models\AuditLog::record('login', 'Signed in via magic link');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        \App\Models\AuditLog::record('logout', 'Signed out');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
