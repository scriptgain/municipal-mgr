<?php

namespace App\Services\Captcha\Providers;

use App\Services\Captcha\CaptchaProvider;
use App\Services\Captcha\CaptchaResult;
use App\Services\Captcha\CaptchaSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Shared machinery for the three token-and-siteverify providers: reCAPTCHA,
 * hCaptcha and Turnstile. They differ only in endpoints, field names and a few
 * strings, so each concrete class is a thin descriptor over this base.
 *
 * The secret key is used only in the server-to-server verify call. It is never
 * placed in rendered markup and never written to a log line.
 */
abstract class AbstractHttpProvider implements CaptchaProvider
{
    abstract protected function verifyUrl(): string;

    abstract protected function scriptUrl(): string;

    /** The POST field the vendor widget populates with its token. */
    abstract protected function responseField(): string;

    /** CSS class the vendor script looks for to mount the widget. */
    abstract protected function widgetClass(): string;

    abstract protected function siteKeySetting(): string;

    abstract protected function secretKeySetting(): string;

    public function isThirdParty(): bool
    {
        return true;
    }

    public function siteKey(): string
    {
        return (string) CaptchaSettings::get($this->siteKeySetting(), '');
    }

    protected function secretKey(): string
    {
        return (string) CaptchaSettings::get($this->secretKeySetting(), '');
    }

    public function isConfigured(): bool
    {
        return $this->siteKey() !== '' && $this->secretKey() !== '';
    }

    public function scripts(): array
    {
        return $this->isConfigured() ? [$this->scriptUrl()] : [];
    }

    public function widget(): string
    {
        if (! $this->isConfigured()) {
            return '<p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200">Spam protection is not fully configured. Add the site and secret keys in Settings.</p>';
        }

        $class = e($this->widgetClass());
        $site = e($this->siteKey());

        return '<div class="' . $class . '" data-sitekey="' . $site . '"></div>';
    }

    public function verify(Request $request): CaptchaResult
    {
        if (! $this->isConfigured()) {
            // No keys: treat as a service that cannot answer, so the fail policy
            // decides. Login stays closed; a pothole report is not blocked by a
            // half-finished setup.
            return CaptchaResult::unreachable('Spam protection is not configured.');
        }

        $token = (string) $request->input($this->responseField(), '');
        if ($token === '') {
            return CaptchaResult::fail('Please complete the verification challenge.');
        }

        $data = $this->siteVerify($token, $request->ip());
        if ($data === null) {
            return CaptchaResult::unreachable();
        }

        if (! ($data['success'] ?? false)) {
            return CaptchaResult::fail('Verification failed. Please try again.');
        }

        return CaptchaResult::pass('', isset($data['score']) ? (float) $data['score'] : null);
    }

    public function selfTest(): CaptchaResult
    {
        if ($this->siteKey() === '' || $this->secretKey() === '') {
            return CaptchaResult::fail('Add both the site key and the secret key, then save, before testing.');
        }

        $data = $this->siteVerify('mm-selftest-token', null);
        if ($data === null) {
            return CaptchaResult::unreachable('Could not reach ' . $this->label() . '. Check the server\'s outbound connectivity.');
        }

        $codes = (array) ($data['error-codes'] ?? []);
        if (array_intersect($codes, ['invalid-input-secret', 'invalid-keys', 'bad-request'])) {
            return CaptchaResult::fail('Reached ' . $this->label() . ', but the secret key was rejected. Re-check it.');
        }
        if (in_array('missing-input-secret', $codes, true)) {
            return CaptchaResult::fail('No secret key is configured for ' . $this->label() . '.');
        }

        // success=true (test keys) or a plain bad-token rejection both prove the
        // endpoint answered and accepted our secret.
        return CaptchaResult::pass('Reached ' . $this->label() . ' and the secret key was accepted.');
    }

    /**
     * @return array<string,mixed>|null  Decoded JSON, or null on a transport error.
     */
    protected function siteVerify(string $token, ?string $ip): ?array
    {
        try {
            $response = Http::asForm()->timeout(5)->post($this->verifyUrl(), array_filter([
                'secret' => $this->secretKey(),
                'response' => $token,
                'remoteip' => $ip,
            ]));
        } catch (\Throwable $e) {
            return null; // network/DNS/timeout: the caller treats this as unreachable
        }

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : null;
    }
}
