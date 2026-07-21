<?php

namespace App\Services\Captcha\Providers;

use App\Services\Captcha\CaptchaProvider;
use App\Services\Captcha\CaptchaResult;
use App\Services\Captcha\CaptchaSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * The "our own" challenge: no third party, no external script, no cookies.
 *
 * A rotating question (simple arithmetic, or a word question) whose answer is
 * carried in an ENCRYPTED token, so the client can neither read it nor forge
 * one. Each token has an issue time and a one-time nonce, so a correct answer
 * cannot be replayed once it has been spent.
 *
 * This is the option a privacy-strict or air-gapped municipality picks, and it
 * is the shipped default because it works with zero configuration.
 */
class BuiltinChallengeProvider implements CaptchaProvider
{
    private const TTL = 1800;               // a challenge is good for 30 minutes
    public const TOKEN_FIELD = 'captcha_challenge';
    public const ANSWER_FIELD = 'captcha_answer';

    public function key(): string
    {
        return 'builtin';
    }

    public function label(): string
    {
        return 'Built-In Challenge (Our Own)';
    }

    public function description(): string
    {
        return 'A signed question answered on your own server. No third party, no external script, no cookies. Best for privacy-strict or air-gapped sites.';
    }

    public function isThirdParty(): bool
    {
        return false;
    }

    public function isConfigured(): bool
    {
        return true; // needs no keys
    }

    public function scripts(): array
    {
        return [];
    }

    public function widget(): string
    {
        [$question, $answer] = $this->makeChallenge();
        $token = e($this->issueToken($answer));
        $q = e($question);
        $tokenField = e(self::TOKEN_FIELD);
        $answerField = e(self::ANSWER_FIELD);

        // A real <label> tied to the input, per the accessibility rule.
        return <<<HTML
            <div class="rounded-lg bg-slate-50 p-4 ring-1 ring-slate-200">
                <label for="{$answerField}" class="block text-sm font-medium text-slate-700">
                    Anti-Spam Question: {$q} <span class="text-rose-600" aria-hidden="true">*</span>
                </label>
                <input id="{$answerField}" name="{$answerField}" type="text" inputmode="text"
                       autocomplete="off" required
                       class="mt-1.5 block w-40 max-w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                <input type="hidden" name="{$tokenField}" value="{$token}">
                <p class="mt-1.5 text-xs text-slate-500">This confirms you are a person, not an automated script.</p>
            </div>
            HTML;
    }

    public function verify(Request $request): CaptchaResult
    {
        $token = (string) $request->input(self::TOKEN_FIELD, '');
        $given = (string) $request->input(self::ANSWER_FIELD, '');

        $payload = $this->decodeToken($token);
        if ($payload === null) {
            return CaptchaResult::fail('Your answer could not be checked. Please reload the page and try again.');
        }

        // One-time use: spend the nonce so a captured answer cannot be replayed.
        $spentKey = 'captcha_spent_' . hash('sha256', $payload['n']);
        if (! Cache::add($spentKey, 1, self::TTL)) {
            return CaptchaResult::fail('That answer was already used. Please reload the page and try again.');
        }

        if (! $this->answerMatches($payload['a'], $given)) {
            return CaptchaResult::fail('That answer was not correct. Please try again.');
        }

        return CaptchaResult::pass();
    }

    public function selfTest(): CaptchaResult
    {
        // Prove the sign -> verify pipeline end to end without a browser.
        [, $answer] = $this->makeChallenge();
        $token = $this->issueToken($answer);
        $payload = $this->decodeToken($token);

        if ($payload !== null && $this->answerMatches($payload['a'], (string) $answer)) {
            return CaptchaResult::pass('The built-in challenge signed and verified a test answer successfully.');
        }

        return CaptchaResult::fail('The built-in challenge could not verify its own token. Check that APP_KEY is set.');
    }

    /** @return array{0:string,1:string} [question, answer] */
    private function makeChallenge(): array
    {
        if ((string) CaptchaSettings::get('captcha_builtin_mode', 'arithmetic') === 'word') {
            $bank = [
                ['Type the word "human" to continue.', 'human'],
                ['What color is a clear daytime sky?', 'blue'],
                ['How many days are in a week?', '7'],
                ['What is the opposite of "yes"?', 'no'],
                ['Type the third letter of the alphabet.', 'c'],
            ];
            $pick = $bank[array_rand($bank)];

            return [$pick[0], $pick[1]];
        }

        $a = random_int(1, 9);
        $b = random_int(1, 9);

        return ["What is {$a} + {$b}?", (string) ($a + $b)];
    }

    private function issueToken(string $answer): string
    {
        return Crypt::encryptString(json_encode([
            'a' => $answer,
            't' => time(),
            'n' => Str::random(16),
        ]));
    }

    /** @return array{a:string,t:int,n:string}|null */
    private function decodeToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        try {
            $data = json_decode(Crypt::decryptString($token), true);
        } catch (\Throwable $e) {
            return null;
        }
        if (! is_array($data) || ! isset($data['a'], $data['t'], $data['n'])) {
            return null;
        }
        if (time() - (int) $data['t'] > self::TTL) {
            return null;
        }

        return ['a' => (string) $data['a'], 't' => (int) $data['t'], 'n' => (string) $data['n']];
    }

    private function answerMatches(string $expected, string $given): bool
    {
        $norm = fn (string $v) => Str::lower(trim($v));

        return $norm($expected) !== '' && hash_equals($norm($expected), $norm($given));
    }
}
