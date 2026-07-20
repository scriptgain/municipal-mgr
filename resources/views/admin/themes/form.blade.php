<x-layouts.app :title="$title">
    <x-page-header :title="$theme->exists ? $theme->name : 'New Theme'" icon="star"
                   :subtitle="$theme->exists ? $theme->slug : 'Start from the shipped tokens and change what you need.'">
        <x-slot:actions>
            <x-button :href="route('settings.themes.index')" variant="ghost" size="sm" icon="chevron-left">All Themes</x-button>
            @if ($theme->exists)
                <x-button :href="$theme->previewUrl()" target="_blank" rel="noopener" variant="secondary" size="sm" icon="eye">Preview On The Site</x-button>
                <x-button :href="route('settings.themes.export', $theme)" variant="secondary" size="sm" icon="download">Export</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <form method="POST"
          action="{{ $theme->exists ? route('settings.themes.update', $theme) : route('settings.themes.store') }}"
          data-theme-form>
        @csrf
        @if ($theme->exists)@method('PUT')@endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] items-start">
            {{-- ------------- Fields ------------- --}}
            <div class="min-w-0 space-y-6">
                <x-card title="Identity">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="Theme Name" for="theme-name" required :error="$errors->first('name')">
                            <x-input name="name" id="theme-name" value="{{ old('name', $theme->name) }}" required maxlength="80" />
                        </x-field>
                        <x-field label="Description" for="theme-description" :error="$errors->first('description')"
                                 hint="What this look is for. Shown on the theme card.">
                            <x-input name="description" id="theme-description" value="{{ old('description', $theme->description) }}" maxlength="255" />
                        </x-field>
                    </div>
                </x-card>

                <div class="section-divider"></div>

                <x-tabs :tabs="[
                    'colour' => ['label' => 'Colour', 'icon' => 'star'],
                    'type' => ['label' => 'Typography', 'icon' => 'book'],
                    'shape' => ['label' => 'Shape And Rhythm', 'icon' => 'dashboard'],
                    'assets' => ['label' => 'Logo And Favicon', 'icon' => 'building'],
                ]">
                    <x-tab-panel name="colour">
                        <x-card title="Brand Colours" subtitle="The brand colour generates the whole 50 to 950 ramp automatically.">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-field label="Brand" for="token-brand" hint="Buttons, links, active states." :error="$errors->first('tokens.brand')">
                                    <div class="flex items-center gap-2">
                                        <input type="color" class="mm-swatch" data-color-picker="token-brand"
                                               value="{{ old('tokens.brand', $tokens['brand']) }}" aria-label="Pick Brand Colour">
                                        <x-input name="tokens[brand]" id="token-brand" data-theme-token="brand" data-shipped="{{ $defaults['brand'] }}"
                                                 value="{{ old('tokens.brand', $tokens['brand']) }}" pattern="^#[0-9a-fA-F]{6}$" />
                                    </div>
                                </x-field>
                                <x-field label="Accent" for="token-accent" hint="Ceremonial trim, rules, eyebrows. Never body text." :error="$errors->first('tokens.accent')">
                                    <div class="flex items-center gap-2">
                                        <input type="color" class="mm-swatch" data-color-picker="token-accent"
                                               value="{{ old('tokens.accent', $tokens['accent']) }}" aria-label="Pick Accent Colour">
                                        <x-input name="tokens[accent]" id="token-accent" data-theme-token="accent" data-shipped="{{ $defaults['accent'] }}"
                                                 value="{{ old('tokens.accent', $tokens['accent']) }}" pattern="^#[0-9a-fA-F]{6}$" />
                                    </div>
                                </x-field>
                                <x-field label="Chrome" for="token-chrome" hint="The dark top utility bar and the public footer." :error="$errors->first('tokens.chrome')">
                                    <div class="flex items-center gap-2">
                                        <input type="color" class="mm-swatch" data-color-picker="token-chrome"
                                               value="{{ old('tokens.chrome', $tokens['chrome']) }}" aria-label="Pick Chrome Colour">
                                        <x-input name="tokens[chrome]" id="token-chrome" data-theme-token="chrome" data-shipped="{{ $defaults['chrome'] }}"
                                                 value="{{ old('tokens.chrome', $tokens['chrome']) }}" pattern="^#[0-9a-fA-F]{6}$" />
                                    </div>
                                </x-field>
                                <x-field label="Chrome Soft" for="token-chrome-soft" hint="The lighter step in the chrome gradients." :error="$errors->first('tokens.chrome_soft')">
                                    <div class="flex items-center gap-2">
                                        <input type="color" class="mm-swatch" data-color-picker="token-chrome-soft"
                                               value="{{ old('tokens.chrome_soft', $tokens['chrome_soft']) }}" aria-label="Pick Chrome Soft Colour">
                                        <x-input name="tokens[chrome_soft]" id="token-chrome-soft" data-theme-token="chrome_soft" data-shipped="{{ $defaults['chrome_soft'] }}"
                                                 value="{{ old('tokens.chrome_soft', $tokens['chrome_soft']) }}" pattern="^#[0-9a-fA-F]{6}$" />
                                    </div>
                                </x-field>
                            </div>
                        </x-card>
                    </x-tab-panel>

                    <x-tab-panel name="type">
                        <x-card title="Typography" subtitle="Font stacks are CSS font families. Keep a system fallback at the end so text renders everywhere.">
                            <div class="space-y-5">
                                <x-field label="Body Font Stack" for="token-font-sans" :error="$errors->first('tokens.font_sans')">
                                    <x-input name="tokens[font_sans]" id="token-font-sans" data-theme-token="font_sans" data-shipped="{{ $defaults['font_sans'] }}"
                                             value="{{ old('tokens.font_sans', $tokens['font_sans']) }}" maxlength="255" />
                                </x-field>
                                <x-field label="Display Font Stack" for="token-font-display"
                                         hint="Headings and the ceremonial wordmark." :error="$errors->first('tokens.font_display')">
                                    <x-input name="tokens[font_display]" id="token-font-display" data-theme-token="font_display" data-shipped="{{ $defaults['font_display'] }}"
                                             value="{{ old('tokens.font_display', $tokens['font_display']) }}" maxlength="255" />
                                </x-field>
                                <x-field label="Type Scale" for="token-font-scale"
                                         hint="Multiplies the root font size. 1 is the shipped 16px base." :error="$errors->first('tokens.font_scale')">
                                    <div class="flex items-center gap-3">
                                        <input type="range" name="tokens[font_scale]" id="token-font-scale" data-theme-token="font_scale" data-shipped="{{ $defaults['font_scale'] }}"
                                               min="0.75" max="1.5" step="0.05" value="{{ old('tokens.font_scale', $tokens['font_scale']) }}"
                                               class="mm-range min-w-0 flex-1">
                                        <output class="w-14 shrink-0 text-right text-sm tabular text-slate-700" data-output-for="token-font-scale"></output>
                                    </div>
                                </x-field>
                            </div>
                        </x-card>
                    </x-tab-panel>

                    <x-tab-panel name="shape">
                        <x-card title="Shape And Rhythm" subtitle="Corner roundness and the spacing step every margin and padding is built from.">
                            <div class="space-y-5">
                                <x-field label="Corner Radius" for="token-radius"
                                         hint="0 gives square corners. 1 is the shipped roundness." :error="$errors->first('tokens.radius')">
                                    <div class="flex items-center gap-3">
                                        <input type="range" name="tokens[radius]" id="token-radius" data-theme-token="radius" data-shipped="{{ $defaults['radius'] }}"
                                               min="0" max="3" step="0.25" value="{{ old('tokens.radius', $tokens['radius']) }}"
                                               class="mm-range min-w-0 flex-1">
                                        <output class="w-14 shrink-0 text-right text-sm tabular text-slate-700" data-output-for="token-radius"></output>
                                    </div>
                                </x-field>
                                <x-field label="Spacing Rhythm" for="token-spacing"
                                         hint="Multiplies the 0.25rem spacing step. Higher is roomier." :error="$errors->first('tokens.spacing')">
                                    <div class="flex items-center gap-3">
                                        <input type="range" name="tokens[spacing]" id="token-spacing" data-theme-token="spacing" data-shipped="{{ $defaults['spacing'] }}"
                                               min="0.75" max="1.5" step="0.05" value="{{ old('tokens.spacing', $tokens['spacing']) }}"
                                               class="mm-range min-w-0 flex-1">
                                        <output class="w-14 shrink-0 text-right text-sm tabular text-slate-700" data-output-for="token-spacing"></output>
                                    </div>
                                </x-field>
                                <x-field label="Chrome Treatment" hint="Whether the utility bar and footer read as a dark or light band.">
                                    <x-toggle name="tokens[chrome_treatment_dark]" :checked="($tokens['chrome_treatment'] ?? 'dark') === 'dark'"
                                              label="Dark Chrome" description="On is the civic default: a dark bar above a light page." />
                                    <input type="hidden" name="tokens[chrome_treatment]" data-chrome-treatment
                                           value="{{ old('tokens.chrome_treatment', $tokens['chrome_treatment']) }}">
                                </x-field>
                            </div>
                        </x-card>
                    </x-tab-panel>

                    <x-tab-panel name="assets">
                        <x-card title="Logo And Favicon" subtitle="Paths on this site, or full https URLs. Leave blank to keep the brand glyph.">
                            <div class="space-y-5">
                                <x-field label="Logo URL" for="token-logo" :error="$errors->first('tokens.logo_url')"
                                         hint="Upload the image in the File Manager first, then paste its URL here.">
                                    <x-input name="tokens[logo_url]" id="token-logo" data-theme-token="logo_url" data-shipped="{{ $defaults['logo_url'] }}"
                                             value="{{ old('tokens.logo_url', $tokens['logo_url']) }}" maxlength="255" placeholder="/storage/uploads/seal.svg" />
                                </x-field>
                                <x-field label="Favicon URL" for="token-favicon" :error="$errors->first('tokens.favicon_url')"
                                         hint="Blank uses the accent-tinted brand glyph the product generates.">
                                    <x-input name="tokens[favicon_url]" id="token-favicon" data-theme-token="favicon_url" data-shipped="{{ $defaults['favicon_url'] }}"
                                             value="{{ old('tokens.favicon_url', $tokens['favicon_url']) }}" maxlength="255" placeholder="/storage/uploads/favicon.png" />
                                </x-field>
                            </div>
                        </x-card>
                    </x-tab-panel>
                </x-tabs>

                <div class="flex flex-wrap items-center gap-2">
                    <x-button type="submit" icon="check">{{ $theme->exists ? 'Save Theme' : 'Create Theme' }}</x-button>
                    @if ($theme->exists && ! $theme->is_active)
                        <x-confirm-action
                            name="activate-this-theme"
                            :action="route('settings.themes.activate', $theme)"
                            title="Activate &quot;{{ $theme->name }}&quot;?"
                            message="Every visitor will see this look immediately. Save your changes first if you have unsaved edits."
                            confirm="Activate"
                            confirmIcon="check">
                            <x-button type="button" variant="secondary" icon="star">Make This The Active Theme</x-button>
                        </x-confirm-action>
                    @endif
                    <x-button type="button" variant="ghost" icon="restore" data-theme-reset>Reset Fields To Shipped Values</x-button>
                </div>
            </div>

            {{-- ------------- Live preview ------------- --}}
            <div class="min-w-0 lg:sticky lg:top-20">
                <x-card flush title="Live Preview" subtitle="Updates as you type. Nothing is saved until you press Save.">
                    <div class="mm-theme-preview" data-theme-preview>
                        <div class="mm-tp-chrome" data-preview-chrome>
                            <span class="mm-tp-mark" data-preview-mark>{{ config('brand.name') }}</span>
                            <span class="mm-tp-sub">Staff Panel</span>
                        </div>
                        <div class="mm-tp-hero" data-preview-hero>
                            <span class="mm-tp-eyebrow" data-preview-eyebrow>Village Of Example</span>
                            <span class="mm-tp-title" data-preview-title>Serving Our Residents</span>
                            <span class="mm-tp-rule" data-preview-rule></span>
                        </div>
                        <div class="mm-tp-body" data-preview-body>
                            <div class="mm-tp-card" data-preview-card>
                                <span class="mm-tp-cardtitle">Public Notice</span>
                                <span class="mm-tp-text">Body text renders in the body font stack at the chosen type scale.</span>
                                <span class="mm-tp-actions">
                                    <span class="mm-tp-btn" data-preview-btn>Primary</span>
                                    <span class="mm-tp-btn-ghost" data-preview-btn-ghost>Secondary</span>
                                </span>
                            </div>
                            <div class="mm-tp-swatches" data-preview-swatches></div>
                        </div>
                        <div class="mm-tp-footer" data-preview-footer>Footer</div>
                    </div>
                </x-card>
                <p class="mt-3 text-xs text-slate-500">
                    The panel above is an approximation. Use Preview On The Site to render the real public site with this theme, visible only to you.
                </p>
            </div>
        </div>
    </form>

    {{-- Plain DOM rather than Alpine.data(): this file loads after the Alpine
         bundle, so a registration here would never run. --}}
    <script defer src="{{ asset_v('js/themes.js') }}"></script>
</x-layouts.app>
