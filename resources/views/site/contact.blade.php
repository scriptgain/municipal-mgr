<x-layouts.public title="Contact Us">
    <x-site.page-hero title="Contact Us"
                      subtitle="Reach Village Hall, a specific department, or report an issue."
                      :crumbs="[['label' => 'Contact']]" />

    <x-site.section :divider="false">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="min-w-0 lg:col-span-2 space-y-8">
                <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm">
                    <h2 class="font-display text-2xl font-semibold text-slate-900">{{ $siteName }} Offices</h2>
                    <span class="seal-rule mt-3 mb-5"></span>
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Address</h3>
                            <address class="mt-2 not-italic leading-relaxed text-slate-700">
                                @if ($site['contact_address'])<span class="block">{{ $site['contact_address'] }}</span>@endif
                                @if ($site['contact_city_state_zip'])<span class="block">{{ $site['contact_city_state_zip'] }}</span>@endif
                            </address>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Hours</h3>
                            <p class="mt-2 text-slate-700">{{ $site['contact_hours'] }}</p>
                            @if ($site['contact_after_hours'])
                                <p class="mt-2 text-sm text-slate-500">After Hours: {{ $site['contact_after_hours'] }}</p>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Phone</h3>
                            <p class="mt-2">
                                @if ($site['contact_phone'])
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact_phone']) }}" class="text-brand-700 hover:underline tabular">{{ $site['contact_phone'] }}</a>
                                @endif
                                @if ($site['contact_fax'])<span class="block text-sm text-slate-500 tabular">Fax: {{ $site['contact_fax'] }}</span>@endif
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Email</h3>
                            <p class="mt-2">
                                @if ($site['contact_email'])
                                    <a href="mailto:{{ $site['contact_email'] }}" class="break-all text-brand-700 hover:underline">{{ $site['contact_email'] }}</a>
                                @endif
                            </p>
                        </div>
                    </div>

                    @if ($site['contact_map_embed'])
                        <div class="mt-6 overflow-hidden rounded-xl ring-1 ring-slate-200 [&_iframe]:w-full [&_iframe]:h-80 [&_iframe]:border-0">
                            {!! $site['contact_map_embed'] !!}
                        </div>
                    @endif
                </div>

                <div class="section-divider pt-8">
                    <h2 class="font-display text-2xl font-semibold text-slate-900">Contact A Department Directly</h2>
                    <span class="seal-rule mt-3 mb-5"></span>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($departments as $department)
                            <div class="rounded-xl bg-white p-5 ring-1 ring-slate-200">
                                <h3 class="font-semibold text-slate-900">
                                    <a href="{{ route('site.departments.show', $department->slug) }}" class="hover:text-brand-700 hover:underline">{{ $department->name }}</a>
                                </h3>
                                <dl class="mt-2 space-y-1 text-sm">
                                    @if ($department->phone)
                                        <div class="flex items-center gap-2 text-slate-600">
                                            <x-icon name="phone" class="w-4 h-4 shrink-0 text-slate-400" />
                                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $department->phone) }}" class="hover:text-brand-700 hover:underline">{{ $department->phone }}</a>
                                        </div>
                                    @endif
                                    @if ($department->email)
                                        <div class="flex items-center gap-2 text-slate-600">
                                            <x-icon name="envelope" class="w-4 h-4 shrink-0 text-slate-400" />
                                            <a href="mailto:{{ $department->email }}" class="truncate hover:text-brand-700 hover:underline">{{ $department->email }}</a>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <aside class="space-y-6">
                <a href="{{ route('site.report') }}" class="block rounded-2xl bg-brand-700 p-6 text-white transition hover:bg-brand-800">
                    <x-icon name="bolt" class="w-8 h-8" />
                    <h2 class="mt-3 text-lg font-semibold">Report An Issue</h2>
                    <p class="mt-1 text-sm text-brand-100">Potholes, streetlights, water problems, and more.</p>
                </a>

                @if ($contactForm)
                    <a href="{{ route('site.forms.show', $contactForm->slug) }}" class="block rounded-2xl bg-white p-6 ring-1 ring-slate-200 transition hover:ring-brand-300">
                        <x-icon name="envelope" class="w-8 h-8 text-brand-700" />
                        <h2 class="mt-3 text-lg font-semibold text-slate-900">Send Us A Message</h2>
                        <p class="mt-1 text-sm text-slate-600">Use our contact form and we will route it to the right office.</p>
                    </a>
                @endif

                <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">Staff Directory</h2>
                    <span class="seal-rule mt-3 mb-4"></span>
                    <p class="text-sm text-slate-600">Looking for a specific person? The full directory lists every staff member with a direct line.</p>
                    <a href="{{ route('site.directory') }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline">
                        Browse The Directory <x-icon name="chevron-right" class="w-4 h-4" />
                    </a>
                </div>
            </aside>
        </div>
    </x-site.section>
</x-layouts.public>
