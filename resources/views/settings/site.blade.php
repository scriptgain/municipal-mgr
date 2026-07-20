<x-layouts.app title="Site Identity">
    <x-page-header title="Site Identity" icon="building"
                   subtitle="Who the municipality is, how residents reach it, and what the homepage says.">
    </x-page-header>

    <form method="POST" action="{{ route('settings.site.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf @method('PUT')

        <x-tabs :tabs="[
            'identity' => ['label' => 'Identity', 'icon' => 'building'],
            'contact' => ['label' => 'Contact', 'icon' => 'phone'],
            'homepage' => ['label' => 'Homepage', 'icon' => 'home'],
            'social' => ['label' => 'Social And Footer', 'icon' => 'globe'],
        ]">
            <x-tab-panel name="identity">
                <x-card title="Municipality">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="Municipality Name" for="site_name" required
                                 hint="As it should read in the header, e.g. Village Of Secor." :error="$errors->first('site_name')">
                            <x-input id="site_name" name="site_name" :value="old('site_name', $site['site_name'])" required />
                        </x-field>

                        <x-field label="Municipality Type" for="site_kind" :error="$errors->first('site_kind')">
                            <x-select id="site_kind" name="site_kind">
                                @foreach (['City', 'Town', 'Village', 'Township', 'Borough', 'County'] as $kind)
                                    <option value="{{ $kind }}" @selected(old('site_kind', $site['site_kind']) === $kind)>{{ $kind }}</option>
                                @endforeach
                            </x-select>
                        </x-field>

                        <x-field label="State Or Province" for="site_state" :error="$errors->first('site_state')">
                            <x-input id="site_state" name="site_state" :value="old('site_state', $site['site_state'])" />
                        </x-field>

                        <x-field label="Motto Or Established Line" for="site_motto" :error="$errors->first('site_motto')">
                            <x-input id="site_motto" name="site_motto" :value="old('site_motto', $site['site_motto'])" />
                        </x-field>

                        <x-field label="Official Seal" for="seal" hint="Shown in the header and footer. A transparent PNG works best." :error="$errors->first('seal')">
                            <input id="seal" name="seal" type="file" accept="image/*"
                                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                            @if ($site['site_seal_path'])
                                <img src="{{ municipal_upload_url($site['site_seal_path']) }}" alt="Current official seal" class="mt-3 h-16 w-16 object-contain">
                            @endif
                        </x-field>

                        <x-field label="Logo" for="logo" :error="$errors->first('logo')">
                            <input id="logo" name="logo" type="file" accept="image/*"
                                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                            @if ($site['site_logo_path'])
                                <img src="{{ municipal_upload_url($site['site_logo_path']) }}" alt="Current logo" class="mt-3 h-12 object-contain">
                            @endif
                        </x-field>
                    </div>
                </x-card>
            </x-tab-panel>

            <x-tab-panel name="contact">
                <x-card title="Village Hall Contact">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="Street Address" for="contact_address" :error="$errors->first('contact_address')">
                            <x-input id="contact_address" name="contact_address" :value="old('contact_address', $site['contact_address'])" />
                        </x-field>
                        <x-field label="City, State, ZIP" for="contact_city_state_zip" :error="$errors->first('contact_city_state_zip')">
                            <x-input id="contact_city_state_zip" name="contact_city_state_zip" :value="old('contact_city_state_zip', $site['contact_city_state_zip'])" />
                        </x-field>
                        <x-field label="Main Phone" for="contact_phone" :error="$errors->first('contact_phone')">
                            <x-input id="contact_phone" name="contact_phone" :value="old('contact_phone', $site['contact_phone'])" />
                        </x-field>
                        <x-field label="Fax" for="contact_fax" :error="$errors->first('contact_fax')">
                            <x-input id="contact_fax" name="contact_fax" :value="old('contact_fax', $site['contact_fax'])" />
                        </x-field>
                        <x-field label="General Email" for="contact_email" :error="$errors->first('contact_email')">
                            <x-input id="contact_email" name="contact_email" type="email" :value="old('contact_email', $site['contact_email'])" />
                        </x-field>
                        <x-field label="Office Hours" for="contact_hours" :error="$errors->first('contact_hours')">
                            <x-input id="contact_hours" name="contact_hours" :value="old('contact_hours', $site['contact_hours'])" />
                        </x-field>
                        <x-field label="After Hours Emergencies" for="contact_after_hours" class="sm:col-span-2"
                                 hint="What a resident should do at 2am – the number that actually gets answered." :error="$errors->first('contact_after_hours')">
                            <x-input id="contact_after_hours" name="contact_after_hours" :value="old('contact_after_hours', $site['contact_after_hours'])" />
                        </x-field>
                        <x-field label="Map Embed Code" for="contact_map_embed" class="sm:col-span-2"
                                 hint="Paste an embed iframe from your mapping provider." :error="$errors->first('contact_map_embed')">
                            <textarea id="contact_map_embed" name="contact_map_embed" rows="3"
                                      class="block w-full rounded-lg border-0 py-2 px-3 font-mono text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('contact_map_embed', $site['contact_map_embed']) }}</textarea>
                        </x-field>
                        <x-field label="Accessibility Contact" for="accessibility_contact" class="sm:col-span-2"
                                 hint="Named person or office for accessibility complaints. Required by most accessibility policies." :error="$errors->first('accessibility_contact')">
                            <x-input id="accessibility_contact" name="accessibility_contact" :value="old('accessibility_contact', $site['accessibility_contact'])" />
                        </x-field>
                    </div>
                </x-card>
            </x-tab-panel>

            <x-tab-panel name="homepage">
                <x-card title="Homepage Hero">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="Hero Heading" for="site_hero_heading" class="sm:col-span-2" :error="$errors->first('site_hero_heading')">
                            <x-input id="site_hero_heading" name="site_hero_heading" :value="old('site_hero_heading', $site['site_hero_heading'])" />
                        </x-field>
                        <x-field label="Hero Subheading" for="site_hero_subheading" class="sm:col-span-2" :error="$errors->first('site_hero_subheading')">
                            <textarea id="site_hero_subheading" name="site_hero_subheading" rows="2"
                                      class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('site_hero_subheading', $site['site_hero_subheading']) }}</textarea>
                        </x-field>
                        <x-field label="Hero Background Image" for="hero" :error="$errors->first('hero')">
                            <input id="hero" name="hero" type="file" accept="image/*"
                                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                            @if ($site['site_hero_image_path'])
                                <img src="{{ municipal_upload_url($site['site_hero_image_path']) }}" alt="Current hero image" class="mt-3 h-24 w-full rounded-lg object-cover ring-1 ring-slate-200">
                            @endif
                        </x-field>
                        <x-field label="Pay A Bill Link" for="pay_bill_url" hint="Your payment portal. Used by the homepage quick link." :error="$errors->first('pay_bill_url')">
                            <x-input id="pay_bill_url" name="pay_bill_url" type="url" :value="old('pay_bill_url', $site['pay_bill_url'])" />
                        </x-field>
                        <x-field label="Meeting Live Stream Link" for="meeting_stream_url" class="sm:col-span-2" :error="$errors->first('meeting_stream_url')">
                            <x-input id="meeting_stream_url" name="meeting_stream_url" type="url" :value="old('meeting_stream_url', $site['meeting_stream_url'])" />
                        </x-field>
                    </div>
                    <p class="section-divider mt-6 pt-5 text-sm text-slate-500">
                        The quick-link tiles themselves are managed under
                        <a href="{{ route('menus.index') }}" class="font-medium text-brand-700 hover:underline">Navigation Menus</a>.
                    </p>
                </x-card>
            </x-tab-panel>

            <x-tab-panel name="social">
                <x-card title="Social Accounts And Footer">
                    <div class="grid gap-5 sm:grid-cols-2">
                        @foreach ([
                            'social_facebook' => 'Facebook Page',
                            'social_x' => 'X Account',
                            'social_youtube' => 'YouTube Channel',
                            'social_instagram' => 'Instagram Account',
                            'social_nextdoor' => 'Nextdoor Page',
                        ] as $key => $networkLabel)
                            <x-field :label="$networkLabel" :for="$key" :error="$errors->first($key)">
                                <x-input :id="$key" :name="$key" type="url" :value="old($key, $site[$key])" />
                            </x-field>
                        @endforeach

                        <x-field label="Footer Note" for="footer_note" class="sm:col-span-2"
                                 hint="Shown next to the copyright line." :error="$errors->first('footer_note')">
                            <x-input id="footer_note" name="footer_note" :value="old('footer_note', $site['footer_note'])" />
                        </x-field>
                    </div>
                </x-card>
            </x-tab-panel>
        </x-tabs>

        <div class="section-divider pt-5 flex justify-end">
            <x-button type="submit" icon="check">Save Site Settings</x-button>
        </div>
    </form>
</x-layouts.app>
