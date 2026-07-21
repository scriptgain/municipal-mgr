<?php

namespace App\Services\Captcha;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * The always-on baseline: a honeypot field plus a minimum-time-to-submit trap.
 *
 * This layer is independent of whichever provider is selected and runs even
 * when the provider is "None", so a form is never completely unprotected and is
 * never broken by a missing key. It needs no third party, no JavaScript, and no
 * cookies, which also makes it the accessible, privacy-safe floor under every
 * form.
 *
 * Accessibility: the honeypot is a real labelled field kept off-screen with
 * position, NOT display:none — some bots skip display:none fields, and a
 * screen-reader user is steered past it with aria-hidden + tabindex="-1".
 */
class HoneypotGuard
{
    /** Reuses the field name the report and form-builder intakes already trap. */
    public const HONEYPOT = 'website';

    /** Signed, encrypted render timestamp: tamper-proof, so it can't be forged. */
    public const TIME_FIELD = 'mm_rendered';

    /** A stale page is not a bot: allow up to two hours between render and submit. */
    private const MAX_AGE = 7200;

    /** Markup injected into every protected form by the <x-captcha> component. */
    public static function fields(): string
    {
        $token = e(self::issueToken());
        $hp = e(self::HONEYPOT);
        $tf = e(self::TIME_FIELD);

        // Off-screen, not display:none. Labelled for the accessibility tree even
        // though aria-hidden removes it from it, and tabindex -1 keeps keyboard
        // users out of it.
        return <<<HTML
            <div aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
                <label for="{$hp}">Leave This Field Empty</label>
                <input id="{$hp}" name="{$hp}" type="text" tabindex="-1" autocomplete="off" value="">
            </div>
            <input type="hidden" name="{$tf}" value="{$token}">
            HTML;
    }

    public static function issueToken(): string
    {
        return Crypt::encryptString(json_encode([
            't' => time(),
            'n' => Str::random(8),
        ]));
    }

    /**
     * Check both traps. Always "reachable" — there is no external service — so a
     * trip here is a hard rejection regardless of the fail policy.
     */
    public static function check(Request $request): CaptchaResult
    {
        // Honeypot: a human never sees the field, so any value is a bot.
        if (filled($request->input(self::HONEYPOT))) {
            return CaptchaResult::fail('Your submission was flagged as automated.');
        }

        $min = CaptchaSettings::minSeconds();
        if ($min > 0) {
            $token = (string) $request->input(self::TIME_FIELD, '');
            $issued = self::decodeTime($token);

            // A present, decodable token that came back impossibly fast is a bot.
            // A missing or unreadable token (a cached page, a proxy that stripped
            // it) is NOT punished — the honeypot still covers that case.
            if ($issued !== null) {
                $elapsed = time() - $issued;
                if ($elapsed < $min) {
                    return CaptchaResult::fail('That was submitted too quickly. Please try again.');
                }
                if ($elapsed > self::MAX_AGE) {
                    return CaptchaResult::fail('This form expired. Please reload the page and try again.');
                }
            }
        }

        return CaptchaResult::pass();
    }

    private static function decodeTime(string $token): ?int
    {
        if ($token === '') {
            return null;
        }
        try {
            $data = json_decode(Crypt::decryptString($token), true);
        } catch (\Throwable $e) {
            return null;
        }

        return isset($data['t']) ? (int) $data['t'] : null;
    }
}
