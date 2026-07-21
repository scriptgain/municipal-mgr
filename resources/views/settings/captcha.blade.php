<x-layouts.app title="Spam Protection">
    <x-page-header title="Spam Protection" icon="shield" subtitle="Stop contact-form and login spam. Choose a provider, or use the built-in challenge that needs no keys.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" href="{{ route('settings.index') }}">Settings</x-button>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-5"><x-alert type="success">{{ session('status') }}</x-alert></div>
    @endif
    @error('captcha')
        <div class="mb-5"><x-alert type="danger">{{ $message }}</x-alert></div>
    @enderror

    <form method="POST" action="{{ route('settings.captcha.update') }}"
          x-data="{ provider: '{{ $active }}', tab: 'provider' }" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Tabs, so the page scans instead of scrolls. --}}
        <div class="flex flex-wrap gap-1 rounded-xl bg-slate-100 p-1 text-sm font-medium">
            <button type="button" @click="tab = 'provider'" :class="tab === 'provider' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 transition">
                <x-icon name="shield" class="w-4 h-4" /> Provider
            </button>
            <button type="button" @click="tab = 'coverage'" :class="tab === 'coverage' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 transition">
                <x-icon name="check-circle" class="w-4 h-4" /> Where It Applies
            </button>
            <button type="button" @click="tab = 'policy'" :class="tab === 'policy' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 transition">
                <x-icon name="settings" class="w-4 h-4" /> Baseline &amp; Policy
            </button>
        </div>

        {{-- ============================ PROVIDER ============================ --}}
        <div x-show="tab === 'provider'" x-cloak class="space-y-6">
            <x-card title="Choose A Provider" subtitle="Only the selected provider runs. The baseline honeypot and time-trap always run underneath it.">
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($providers as $p)
                        <label class="relative flex cursor-pointer items-start gap-3 rounded-xl p-4 ring-1 ring-slate-200 transition hover:ring-brand-300"
                               :class="provider === '{{ $p->key() }}' ? 'bg-brand-50 ring-brand-400' : 'bg-white'">
                            <input type="radio" name="captcha_provider" value="{{ $p->key() }}" x-model="provider"
                                   @checked($active === $p->key())
                                   class="mt-1 h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-600">
                            <span class="min-w-0">
                                <span class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-900">{{ $p->label() }}</span>
                                    @if ($p->isThirdParty())
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 ring-1 ring-slate-200">External</span>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700 ring-1 ring-emerald-200">No Third Party</span>
                                    @endif
                                </span>
                                <span class="mt-1 block text-sm text-slate-500">{{ $p->description() }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </x-card>

            {{-- Built-in challenge options --}}
            <div x-show="provider === 'builtin'" x-cloak>
                <x-card title="Built-In Challenge" subtitle="Answered on your own server. No external requests, no keys.">
                    <x-field label="Question Style" for="captcha_builtin_mode" hint="Arithmetic rotates a simple sum. Word rotates a short question.">
                        <x-select id="captcha_builtin_mode" name="captcha_builtin_mode">
                            <option value="arithmetic" @selected($get('captcha_builtin_mode') === 'arithmetic')>Arithmetic (e.g. What Is 3 + 5?)</option>
                            <option value="word" @selected($get('captcha_builtin_mode') === 'word')>Word Question</option>
                        </x-select>
                    </x-field>
                </x-card>
            </div>

            {{-- reCAPTCHA v2 keys --}}
            <div x-show="provider === 'recaptcha_v2'" x-cloak>
                <x-card title="Google reCAPTCHA v2 Keys">
                    <x-slot:subtitle>
                        Seeded with Google's official <strong>test keys</strong> that always pass. Replace them with your own before go-live.
                    </x-slot:subtitle>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="Site Key" for="captcha_recaptcha_v2_site">
                            <x-input id="captcha_recaptcha_v2_site" name="captcha_recaptcha_v2_site" :value="$get('captcha_recaptcha_v2_site')" placeholder="6Lc..." />
                        </x-field>
                        <x-field label="Secret Key" for="captcha_recaptcha_v2_secret" hint="Leave blank to keep the saved key.">
                            <x-input id="captcha_recaptcha_v2_secret" name="captcha_recaptcha_v2_secret" type="password" autocomplete="new-password" data-lpignore="true" placeholder="••••••••" />
                        </x-field>
                    </div>
                </x-card>
            </div>

            {{-- reCAPTCHA v3 keys + threshold --}}
            <div x-show="provider === 'recaptcha_v3'" x-cloak>
                <x-card title="Google reCAPTCHA v3 Keys">
                    <x-slot:subtitle>
                        Invisible and score-based. There is <strong>no public test key</strong> for v3, so add your own keys before selecting it.
                    </x-slot:subtitle>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="Site Key" for="captcha_recaptcha_v3_site">
                            <x-input id="captcha_recaptcha_v3_site" name="captcha_recaptcha_v3_site" :value="$get('captcha_recaptcha_v3_site')" placeholder="6Lc..." />
                        </x-field>
                        <x-field label="Secret Key" for="captcha_recaptcha_v3_secret" hint="Leave blank to keep the saved key.">
                            <x-input id="captcha_recaptcha_v3_secret" name="captcha_recaptcha_v3_secret" type="password" autocomplete="new-password" data-lpignore="true" placeholder="••••••••" />
                        </x-field>
                        <x-field label="Score Threshold" for="captcha_v3_threshold" hint="0.0 lets everything through, 1.0 is strictest. 0.5 is a sensible start." :error="$errors->first('captcha_v3_threshold')">
                            <x-input id="captcha_v3_threshold" name="captcha_v3_threshold" type="number" step="0.1" min="0" max="1" :value="$get('captcha_v3_threshold')" />
                        </x-field>
                    </div>
                </x-card>
            </div>

            {{-- hCaptcha keys --}}
            <div x-show="provider === 'hcaptcha'" x-cloak>
                <x-card title="hCaptcha Keys">
                    <x-slot:subtitle>
                        Seeded with hCaptcha's official <strong>test keys</strong> that always pass. Replace them with your own before go-live.
                    </x-slot:subtitle>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="Site Key" for="captcha_hcaptcha_site">
                            <x-input id="captcha_hcaptcha_site" name="captcha_hcaptcha_site" :value="$get('captcha_hcaptcha_site')" placeholder="10000000-ffff-..." />
                        </x-field>
                        <x-field label="Secret Key" for="captcha_hcaptcha_secret" hint="Leave blank to keep the saved key.">
                            <x-input id="captcha_hcaptcha_secret" name="captcha_hcaptcha_secret" type="password" autocomplete="new-password" data-lpignore="true" placeholder="••••••••" />
                        </x-field>
                    </div>
                </x-card>
            </div>

            {{-- Turnstile keys --}}
            <div x-show="provider === 'turnstile'" x-cloak>
                <x-card title="Cloudflare Turnstile Keys">
                    <x-slot:subtitle>
                        Seeded with Cloudflare's official <strong>test keys</strong> that always pass. Replace them with your own before go-live.
                    </x-slot:subtitle>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="Site Key" for="captcha_turnstile_site">
                            <x-input id="captcha_turnstile_site" name="captcha_turnstile_site" :value="$get('captcha_turnstile_site')" placeholder="1x00000000000000000000AA" />
                        </x-field>
                        <x-field label="Secret Key" for="captcha_turnstile_secret" hint="Leave blank to keep the saved key.">
                            <x-input id="captcha_turnstile_secret" name="captcha_turnstile_secret" type="password" autocomplete="new-password" data-lpignore="true" placeholder="••••••••" />
                        </x-field>
                    </div>
                </x-card>
            </div>

            {{-- None note --}}
            <div x-show="provider === 'none'" x-cloak>
                <x-alert type="info" title="Baseline Still Active">
                    With no provider selected, the always-on honeypot and time-trap still protect every form, so nothing is left completely open.
                </x-alert>
            </div>

            <x-card title="Test This Configuration" subtitle="Runs a real round-trip against the saved provider. Save first, then test.">
                <div class="flex items-center gap-3">
                    {{-- Associated with the separate test form below via form="", so
                         it never submits the settings form. --}}
                    <x-button type="submit" form="captcha-test-form" variant="secondary" icon="refresh">Run Test</x-button>
                    <p class="text-sm text-slate-500">Built-in verifies its own signing; external providers verify the secret key is reachable and accepted.</p>
                </div>
            </x-card>
        </div>

        {{-- ============================ COVERAGE ============================ --}}
        <div x-show="tab === 'coverage'" x-cloak class="space-y-6">
            <x-card title="Where It Applies" subtitle="Each public entry point has its own switch. The baseline honeypot and time-trap run on all of them regardless.">
                <div class="space-y-5">
                    <x-toggle name="captcha_on_login" :checked="$get('captcha_on_login') === '1'"
                              label="Staff Login" description="Protect /admin/login. Login is the anti-brute-force surface, so this is on by default." />
                    <div class="section-divider pt-5">
                        <x-toggle name="captcha_on_report" :checked="$get('captcha_on_report') === '1'"
                                  label="Report An Issue" description="Protect the /report-an-issue service-request intake." />
                    </div>
                    <div class="section-divider pt-5">
                        <x-toggle name="captcha_on_contact" :checked="$get('captcha_on_contact') === '1'"
                                  label="Contact Form" description="Protect the Contact Us form submission." />
                    </div>
                    <div class="section-divider pt-5">
                        <x-toggle name="captcha_on_forms" :checked="$get('captcha_on_forms') === '1'"
                                  label="Public Forms" description="Protect every other form-builder submission. (Contact Us has its own switch above.)" />
                    </div>
                </div>
            </x-card>
        </div>

        {{-- ========================= BASELINE & POLICY ===================== --}}
        <div x-show="tab === 'policy'" x-cloak class="space-y-6">
            <x-card title="Baseline Protection" subtitle="Always on, independent of the provider, so a form is never fully unprotected and never breaks on a missing key.">
                <x-field label="Minimum Seconds Before Submit" for="captcha_min_seconds"
                         hint="A form submitted faster than this is treated as a bot. Set to 0 to disable the timer. The honeypot cannot be disabled." :error="$errors->first('captcha_min_seconds')">
                    <x-input id="captcha_min_seconds" name="captcha_min_seconds" type="number" min="0" max="60" :value="$get('captcha_min_seconds')" class="max-w-[8rem]" />
                </x-field>
            </x-card>

            <x-card title="Fail Policy" subtitle="What happens when an external provider cannot be reached.">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field label="Staff Login" for="captcha_fail_login"
                             hint="Default: Fail Closed. A provider outage must not become a way past the login challenge.">
                        <x-select id="captcha_fail_login" name="captcha_fail_login">
                            <option value="closed" @selected($get('captcha_fail_login') === 'closed')>Fail Closed (Reject)</option>
                            <option value="open" @selected($get('captcha_fail_login') === 'open')>Fail Open (Allow)</option>
                        </x-select>
                    </x-field>
                    <x-field label="Public Content Forms" for="captcha_fail_public"
                             hint="Default: Fail Open. A Google outage must not stop a resident reporting a pothole. Fail-opens are logged.">
                        <x-select id="captcha_fail_public" name="captcha_fail_public">
                            <option value="open" @selected($get('captcha_fail_public') === 'open')>Fail Open (Allow, Logged)</option>
                            <option value="closed" @selected($get('captcha_fail_public') === 'closed')>Fail Closed (Reject)</option>
                        </x-select>
                    </x-field>
                </div>
            </x-card>
        </div>

        <div class="flex items-center justify-end gap-2">
            <x-button variant="secondary" href="{{ route('settings.index') }}">Cancel</x-button>
            <x-button type="submit" icon="check">Save</x-button>
        </div>
    </form>

    {{-- Separate form so the Run Test button never submits the settings form.
         The test uses the currently SAVED provider and keys. --}}
    <form method="POST" action="{{ route('settings.captcha.test') }}" id="captcha-test-form" class="hidden">
        @csrf
    </form>
</x-layouts.app>
