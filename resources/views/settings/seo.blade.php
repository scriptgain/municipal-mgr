<x-layouts.app title="SEO">
    <x-page-header title="SEO" icon="search"
                   subtitle="Defaults every page falls back to, verification tags, and the staging switch.">
        <x-slot:actions>
            <x-button variant="secondary" icon="shield" :href="route('settings.seo.health')">SEO Health</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($seo['seo_discourage'] === '1')
        <x-alert type="warn" class="mb-6">
            Search engines are being discouraged from this entire site. Every public page sends
            noindex, robots.txt disallows everything, and sitemap.xml returns not found.
            Turn this off under Visibility before launch.
        </x-alert>
    @endif

    <form method="POST" action="{{ route('settings.seo.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf @method('PUT')

        <x-tabs :tabs="[
            'defaults' => ['label' => 'Defaults', 'icon' => 'edit'],
            'visibility' => ['label' => 'Visibility', 'icon' => 'globe'],
            'verification' => ['label' => 'Verification', 'icon' => 'shield'],
            'sitemap' => ['label' => 'Sitemap And Robots', 'icon' => 'database'],
        ]">
            <x-tab-panel name="defaults">
                <x-card title="Fallback Text"
                        subtitle="Used when a page has no Search Appearance values of its own and nothing could be derived from its content.">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="Title Format" for="seo_title_template" class="sm:col-span-2"
                                 hint="Two placeholders: the first is the page title, the second is the municipality name."
                                 :error="$errors->first('seo_title_template')">
                            <x-input id="seo_title_template" name="seo_title_template"
                                     :value="old('seo_title_template', $seo['seo_title_template'])" />
                        </x-field>

                        <x-field label="Default Page Title" for="seo_default_title" class="sm:col-span-2"
                                 hint="A last resort for a page that supplies no title of its own. Usually left blank."
                                 :error="$errors->first('seo_default_title')">
                            <x-input id="seo_default_title" name="seo_default_title"
                                     :value="old('seo_default_title', $seo['seo_default_title'])" />
                        </x-field>

                        <x-field label="Default Meta Description" for="seo_default_description" class="sm:col-span-2"
                                 hint="Shown for pages with nothing better. Around 155 characters. Blank falls back to the site motto."
                                 :error="$errors->first('seo_default_description')">
                            <textarea id="seo_default_description" name="seo_default_description" rows="3" maxlength="500"
                                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('seo_default_description', $seo['seo_default_description']) }}</textarea>
                        </x-field>
                    </div>

                    <div class="section-divider my-6"></div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="Default Social Share Image" for="og_image"
                                 hint="Used when a page has no image of its own. 1200 by 630 works best."
                                 :error="$errors->first('og_image')">
                            <input id="og_image" name="og_image" type="file" accept="image/*"
                                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                            @if ($seo['seo_default_og_image'])
                                <img src="{{ municipal_upload_url($seo['seo_default_og_image']) }}" alt="Current default share image"
                                     class="mt-3 h-24 rounded-lg object-cover ring-1 ring-slate-200">
                            @endif
                        </x-field>

                        <x-field label="X (Twitter) Handle" for="seo_twitter_site"
                                 hint="Including the @ sign. Attributes shared links to the municipality's account."
                                 :error="$errors->first('seo_twitter_site')">
                            <x-input id="seo_twitter_site" name="seo_twitter_site" placeholder="@yourtown"
                                     :value="old('seo_twitter_site', $seo['seo_twitter_site'])" />
                        </x-field>
                    </div>
                </x-card>
            </x-tab-panel>

            <x-tab-panel name="visibility">
                <x-card title="Search Engine Visibility">
                    <div class="space-y-6">
                        <x-toggle name="seo_discourage" :checked="$seo['seo_discourage'] === '1'"
                                  label="Discourage Search Engines From This Site"
                                  description="For staging and pre-launch sites. Every page sends noindex, robots.txt disallows everything, and sitemap.xml stops responding. Turn this off before going live." />

                        <div class="section-divider"></div>

                        <x-toggle name="seo_structured_data" :checked="$seo['seo_structured_data'] === '1'"
                                  label="Publish Structured Data"
                                  description="Adds JSON-LD describing the municipality, news stories, events, meetings, and job openings. This is what produces rich results." />

                        <div class="section-divider"></div>

                        <x-field label="Organization Type" for="seo_organization_type"
                                 hint="How the municipality describes itself in structured data. GovernmentOrganization suits almost every install."
                                 :error="$errors->first('seo_organization_type')">
                            <x-select id="seo_organization_type" name="seo_organization_type">
                                @foreach (['GovernmentOrganization', 'CityHall', 'Organization'] as $type)
                                    <option value="{{ $type }}" @selected(old('seo_organization_type', $seo['seo_organization_type']) === $type)>{{ $type }}</option>
                                @endforeach
                            </x-select>
                        </x-field>
                    </div>
                </x-card>
            </x-tab-panel>

            <x-tab-panel name="verification">
                <x-card title="Site Verification"
                        subtitle="Paste only the content value from the meta tag each service gives you, not the whole tag.">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="Google Search Console" for="seo_google_verification" class="sm:col-span-2"
                                 :error="$errors->first('seo_google_verification')">
                            <x-input id="seo_google_verification" name="seo_google_verification"
                                     :value="old('seo_google_verification', $seo['seo_google_verification'])" />
                        </x-field>

                        <x-field label="Bing Webmaster Tools" for="seo_bing_verification"
                                 :error="$errors->first('seo_bing_verification')">
                            <x-input id="seo_bing_verification" name="seo_bing_verification"
                                     :value="old('seo_bing_verification', $seo['seo_bing_verification'])" />
                        </x-field>

                        <x-field label="Pinterest" for="seo_pinterest_verification"
                                 :error="$errors->first('seo_pinterest_verification')">
                            <x-input id="seo_pinterest_verification" name="seo_pinterest_verification"
                                     :value="old('seo_pinterest_verification', $seo['seo_pinterest_verification'])" />
                        </x-field>
                    </div>
                </x-card>
            </x-tab-panel>

            <x-tab-panel name="sitemap">
                <x-card title="Sitemap">
                    <div class="space-y-6">
                        <x-toggle name="seo_sitemap_enabled" :checked="$seo['seo_sitemap_enabled'] === '1'"
                                  label="Publish sitemap.xml"
                                  description="Lists every public page so search engines find new notices and agendas quickly." />

                        <div class="section-divider"></div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Sitemap</p>
                                <a href="{{ $sitemapUrl }}" target="_blank" rel="noopener"
                                   class="mt-1 block break-all text-sm font-medium text-brand-700 hover:underline">{{ $sitemapUrl }}</a>
                                <p class="mt-2 text-sm text-slate-600">{{ number_format($sitemapTotal) }} public URL(s).</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Robots</p>
                                <a href="{{ $robotsUrl }}" target="_blank" rel="noopener"
                                   class="mt-1 block break-all text-sm font-medium text-brand-700 hover:underline">{{ $robotsUrl }}</a>
                                <p class="mt-2 text-sm text-slate-600">Generated, and it references the sitemap above.</p>
                            </div>
                        </div>
                    </div>
                </x-card>

                <x-card title="What Is Listed" class="mt-6" flush>
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">Section</th>
                                <th scope="col" class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">URLs</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($sitemapCounts as $section => $count)
                                <tr>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center gap-2">
                                            <span @class([
                                                'h-2 w-2 rounded-full',
                                                'bg-emerald-500' => $count > 0,
                                                'bg-slate-300' => $count === 0,
                                            ])></span>
                                            <span class="font-medium text-slate-900">{{ ucfirst($section) }}</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right tabular text-slate-600">{{ number_format($count) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-card>
            </x-tab-panel>
        </x-tabs>

        <div class="section-divider pt-5 flex items-center justify-end gap-2">
            <x-button type="submit" icon="check">Save Changes</x-button>
        </div>
    </form>
</x-layouts.app>
