<x-layouts.public>
    {{-- Hero --}}
    <section class="site-hero relative isolate overflow-hidden bg-brand-900 text-white">
        @if ($site['site_hero_image_path'])
            <img src="{{ municipal_upload_url($site['site_hero_image_path']) }}" alt=""
                 class="absolute inset-0 -z-10 h-full w-full object-cover">
            <div class="absolute inset-0 -z-10 bg-gradient-to-br from-brand-950/95 via-brand-900/85 to-brand-800/70"></div>
        @else
            <div class="site-hero-wash absolute inset-0 -z-10"></div>
        @endif

        <div class="relative {{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 lg:py-24">
            <div class="grid items-center gap-12 lg:grid-cols-12 lg:gap-16">

                {{-- Left: identity and the task a resident came to do --}}
                <div class="min-w-0 lg:col-span-7">
                    <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-seal-200 ring-1 ring-inset ring-white/20 backdrop-blur">
                        <x-icon name="shield" class="w-3.5 h-3.5 shrink-0" />
                        Official Website Of The {{ $siteFormalName }}
                    </p>

                    <h1 class="mt-6 font-display text-4xl sm:text-5xl lg:text-6xl font-semibold leading-[1.05] tracking-tight">
                        {{ $site['site_hero_heading'] ?: 'Welcome To The ' . $siteName }}
                    </h1>
                    <span class="seal-rule mt-6"></span>
                    <p class="mt-6 max-w-2xl text-lg leading-relaxed text-brand-100">
                        {{ $site['site_hero_subheading'] ?: $site['site_motto'] }}
                    </p>

                    {{-- Residents arrive with a task, so search leads --}}
                    <form action="{{ route('site.search') }}" method="GET" role="search"
                          class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <label for="hero-search" class="sr-only">Search This Site</label>
                        <div class="relative flex-1">
                            <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 w-5 h-5 -translate-y-1/2 text-slate-400" />
                            <input id="hero-search" type="search" name="q" autocomplete="off"
                                   placeholder="Search Services, Documents, Meetings"
                                   class="w-full rounded-xl border-0 bg-white py-4 pl-12 pr-4 text-base text-slate-900 shadow-lg ring-1 ring-inset ring-white/20 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-seal-400">
                        </div>
                        <button type="submit"
                                class="inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-seal-500 px-7 py-4 text-base font-semibold text-brand-950 shadow-lg transition hover:bg-seal-400 focus:outline-none focus:ring-2 focus:ring-seal-300 focus:ring-offset-2 focus:ring-offset-brand-900">
                            Search
                        </button>
                    </form>

                    @if (count($heroActions))
                        <div class="mt-7 flex flex-wrap gap-2.5">
                            @foreach ($heroActions as $action)
                                <a href="{{ $action['href'] }}"
                                   class="group inline-flex items-center gap-2.5 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-inset ring-white/25 backdrop-blur transition hover:bg-white/20 hover:ring-white/40">
                                    <x-icon :name="$action['icon'] ?: 'bolt'" class="w-4 h-4 shrink-0 text-seal-300 transition-colors group-hover:text-seal-200" />
                                    {{ $action['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Right: what is actually happening in town. A civic hero that
                     only decorates wastes the most valuable space on the site. --}}
                <div class="min-w-0 lg:col-span-5">
                    <div class="overflow-hidden rounded-2xl bg-white/[0.07] ring-1 ring-inset ring-white/15 backdrop-blur">
                        <div class="flex items-center gap-2.5 border-b border-white/10 px-5 py-4">
                            <x-icon name="clock" class="w-4 h-4 shrink-0 text-seal-300" />
                            <h2 class="text-[11px] font-semibold uppercase tracking-[0.18em] text-seal-200">Town At A Glance</h2>
                        </div>

                        <div class="divide-y divide-white/10">
                            @if ($meetings->isNotEmpty())
                                @php($nextMeeting = $meetings->first())
                                <a href="{{ route('site.meetings') }}" class="group flex items-start gap-4 px-5 py-4 transition hover:bg-white/5">
                                    <span class="flex w-12 shrink-0 flex-col items-center rounded-lg bg-white/10 py-1.5 ring-1 ring-inset ring-white/15">
                                        <span class="text-[10px] font-semibold uppercase tracking-widest text-seal-300">{{ $nextMeeting->meets_at->format('M') }}</span>
                                        <span class="font-display text-lg font-semibold leading-none">{{ $nextMeeting->meets_at->format('j') }}</span>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-200">Next Meeting</span>
                                        <span class="mt-0.5 block font-medium text-white transition group-hover:text-seal-200">{{ $nextMeeting->title }}</span>
                                        <span class="mt-0.5 block text-sm text-brand-200">{{ $nextMeeting->meets_at->format('l, g:i A') }}</span>
                                    </span>
                                </a>
                            @endif

                            @if ($events->isNotEmpty())
                                @php($nextEvent = $events->first())
                                <a href="{{ route('site.events.show', $nextEvent->slug) }}" class="group flex items-start gap-4 px-5 py-4 transition hover:bg-white/5">
                                    <span class="flex w-12 shrink-0 flex-col items-center rounded-lg bg-white/10 py-1.5 ring-1 ring-inset ring-white/15">
                                        <span class="text-[10px] font-semibold uppercase tracking-widest text-seal-300">{{ $nextEvent->starts_at->format('M') }}</span>
                                        <span class="font-display text-lg font-semibold leading-none">{{ $nextEvent->starts_at->format('j') }}</span>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-200">Upcoming Event</span>
                                        <span class="mt-0.5 block font-medium text-white transition group-hover:text-seal-200">{{ $nextEvent->title }}</span>
                                        <span class="mt-0.5 block text-sm text-brand-200">{{ $nextEvent->whenDisplay() }}</span>
                                    </span>
                                </a>
                            @endif

                            @if ($notices->isNotEmpty())
                                @php($latestNotice = $notices->first())
                                <a href="{{ route('site.notices') }}" class="group flex items-start gap-4 px-5 py-4 transition hover:bg-white/5">
                                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-white/10 text-seal-300 ring-1 ring-inset ring-white/15">
                                        <x-icon name="bell" class="w-5 h-5" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-200">Latest Public Notice</span>
                                        <span class="mt-0.5 block font-medium text-white transition group-hover:text-seal-200">{{ $latestNotice->title }}</span>
                                        <span class="mt-0.5 block text-sm text-brand-200">Posted {{ $latestNotice->posted_at?->diffForHumans() }}</span>
                                    </span>
                                </a>
                            @endif
                        </div>

                        <div class="border-t border-white/10 px-5 py-3.5">
                            <a href="{{ route('site.calendar') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-seal-300 transition hover:text-seal-200">
                                View The Full Calendar
                                <x-icon name="chevron-right" class="w-4 h-4 shrink-0" />
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- Quick links --}}
    @if (count($quickLinks))
        <x-site.section tone="muted" :divider="false" title="How Can We Help?"
                        subtitle="The services residents ask for most.">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($quickLinks as $link)
                    <x-site.card-link :href="$link['href']" :title="$link['label']"
                                      :description="$link['description']" :icon="$link['icon'] ?: 'bolt'"
                                      :newTab="$link['new_tab']" />
                @endforeach
            </div>
        </x-site.section>
    @endif

    {{-- Featured story --}}
    @if ($featured)
        <x-site.section>
            <div class="grid items-center gap-8 lg:grid-cols-2">
                <div class="order-2 lg:order-1">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-seal-700">Featured &middot; {{ $featured->category }}</p>
                    <h2 class="mt-3 font-display text-3xl font-semibold tracking-tight text-slate-900">{{ $featured->title }}</h2>
                    <span class="seal-rule mt-4"></span>
                    <p class="mt-5 text-lg leading-relaxed text-slate-600">{{ $featured->teaser(280) }}</p>
                    <a href="{{ route('site.news.show', $featured->slug) }}"
                       class="mt-6 inline-flex items-center gap-2 rounded-lg bg-brand-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-800">
                        Read The Full Story <x-icon name="chevron-right" class="w-4 h-4" />
                    </a>
                </div>
                <div class="order-1 lg:order-2">
                    @if ($featured->image_path)
                        <img src="{{ municipal_upload_url($featured->image_path) }}" alt="{{ $featured->title }}"
                             class="aspect-[16/10] w-full rounded-2xl object-cover ring-1 ring-slate-200">
                    @else
                        <div class="flex aspect-[16/10] w-full items-center justify-center rounded-2xl bg-brand-50 ring-1 ring-brand-200">
                            <x-icon name="megaphone" class="w-16 h-16 text-brand-300" />
                        </div>
                    @endif
                </div>
            </div>
        </x-site.section>
    @endif

    {{-- News + meetings + notices, tabbed so the homepage stays one screen --}}
    <x-site.section tone="muted" title="What's Happening"
                    subtitle="News, upcoming meetings, community events, and legal notices.">
        <x-tabs :tabs="[
            'news' => ['label' => 'News', 'icon' => 'bell'],
            'meetings' => ['label' => 'Meetings', 'icon' => 'clock'],
            'events' => ['label' => 'Events', 'icon' => 'calendar'],
            'notices' => ['label' => 'Public Notices', 'icon' => 'warning'],
        ]">
            <x-tab-panel name="news">
                @if ($news->count())
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($news as $post)
                            <x-site.card-link :href="route('site.news.show', $post->slug)"
                                              :title="$post->title"
                                              :description="$post->teaser()"
                                              :meta="$post->category . ' · ' . $post->published_at?->format(config('municipal.date_format'))" />
                        @endforeach
                    </div>
                    <div class="mt-8 text-center">
                        <a href="{{ route('site.news') }}" class="inline-flex items-center gap-1.5 font-semibold text-brand-700 hover:underline">
                            All News And Announcements <x-icon name="chevron-right" class="w-4 h-4" />
                        </a>
                    </div>
                @else
                    <x-site.empty title="No News Posted Yet" icon="bell"
                                  message="Announcements from Village Hall will appear here." />
                @endif
            </x-tab-panel>

            <x-tab-panel name="meetings">
                @if ($meetings->count())
                    <ul class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 divide-y divide-slate-100">
                        @foreach ($meetings as $meeting)
                            <li class="flex flex-wrap items-center gap-4 p-5">
                                <div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-xl bg-brand-50 text-brand-800 ring-1 ring-brand-200">
                                    <span class="text-xs font-semibold uppercase">{{ $meeting->meets_at->format('M') }}</span>
                                    <span class="text-xl font-bold leading-none tabular">{{ $meeting->meets_at->format('j') }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('site.meetings.show', $meeting->slug) }}" class="font-semibold text-slate-900 hover:text-brand-700 hover:underline">
                                        {{ $meeting->displayTitle() }}
                                    </a>
                                    <p class="mt-0.5 text-sm text-slate-500">
                                        {{ $meeting->meets_at->format(config('municipal.time_format')) }}
                                        @if ($meeting->location) &middot; {{ $meeting->location }} @endif
                                    </p>
                                </div>
                                @if ($meeting->agenda)
                                    <a href="{{ route('site.files.download', $meeting->agenda->slug) }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-800 ring-1 ring-brand-200 transition hover:bg-brand-100">
                                        <x-icon name="download" class="w-4 h-4" /> Agenda
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-8 text-center">
                        <a href="{{ route('site.meetings') }}" class="inline-flex items-center gap-1.5 font-semibold text-brand-700 hover:underline">
                            All Meetings, Agendas, And Minutes <x-icon name="chevron-right" class="w-4 h-4" />
                        </a>
                    </div>
                @else
                    <x-site.empty title="No Upcoming Meetings" icon="clock"
                                  message="Scheduled public meetings will be posted here with their agendas." />
                @endif
            </x-tab-panel>

            <x-tab-panel name="events">
                @if ($events->count())
                    <ul class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 divide-y divide-slate-100">
                        @foreach ($events as $event)
                            <li class="flex flex-wrap items-center gap-4 p-5">
                                <div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-xl bg-seal-100 text-seal-700 ring-1 ring-seal-300">
                                    <span class="text-xs font-semibold uppercase">{{ $event->starts_at->format('M') }}</span>
                                    <span class="text-xl font-bold leading-none tabular">{{ $event->starts_at->format('j') }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('site.events.show', $event->slug) }}" class="font-semibold text-slate-900 hover:text-brand-700 hover:underline">
                                        {{ $event->title }}
                                    </a>
                                    <p class="mt-0.5 text-sm text-slate-500">{{ $event->whenDisplay() }}</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">{{ $event->category }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-8 text-center">
                        <a href="{{ route('site.calendar') }}" class="inline-flex items-center gap-1.5 font-semibold text-brand-700 hover:underline">
                            Full Community Calendar <x-icon name="chevron-right" class="w-4 h-4" />
                        </a>
                    </div>
                @else
                    <x-site.empty title="No Upcoming Events" icon="calendar" />
                @endif
            </x-tab-panel>

            <x-tab-panel name="notices">
                @if ($notices->count())
                    <ul class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 divide-y divide-slate-100">
                        @foreach ($notices as $notice)
                            <li class="flex flex-wrap items-start gap-4 p-5">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                                    <x-icon name="warning" class="w-5 h-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('site.notices.show', $notice->slug) }}" class="font-semibold text-slate-900 hover:text-brand-700 hover:underline">
                                        {{ $notice->title }}
                                    </a>
                                    <p class="mt-0.5 text-sm text-slate-500">
                                        {{ $notice->notice_type }} &middot; Posted {{ $notice->posted_at?->format(config('municipal.date_format')) }}
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-8 text-center">
                        <a href="{{ route('site.notices') }}" class="inline-flex items-center gap-1.5 font-semibold text-brand-700 hover:underline">
                            All Public Notices <x-icon name="chevron-right" class="w-4 h-4" />
                        </a>
                    </div>
                @else
                    <x-site.empty title="No Current Notices" icon="warning" />
                @endif
            </x-tab-panel>
        </x-tabs>
    </x-site.section>

    {{-- Contact strip --}}
    <x-site.section tone="navy" :divider="false">
        <div class="grid gap-8 md:grid-cols-3">
            <div>
                <span class="seal-rule"></span>
                <h2 class="mt-4 font-display text-2xl font-semibold">Visit Village Hall</h2>
                <address class="mt-3 not-italic leading-relaxed text-brand-100">
                    @if ($site['contact_address'])<span class="block">{{ $site['contact_address'] }}</span>@endif
                    @if ($site['contact_city_state_zip'])<span class="block">{{ $site['contact_city_state_zip'] }}</span>@endif
                </address>
            </div>
            <div>
                <span class="seal-rule"></span>
                <h2 class="mt-4 font-display text-2xl font-semibold">Office Hours</h2>
                <p class="mt-3 text-brand-100">{{ $site['contact_hours'] }}</p>
                @if ($site['contact_after_hours'])
                    <p class="mt-2 text-sm text-brand-200">After Hours: {{ $site['contact_after_hours'] }}</p>
                @endif
            </div>
            <div>
                <span class="seal-rule"></span>
                <h2 class="mt-4 font-display text-2xl font-semibold">Get In Touch</h2>
                <p class="mt-3 space-y-1 text-brand-100">
                    @if ($site['contact_phone'])
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact_phone']) }}" class="block hover:text-white hover:underline">{{ $site['contact_phone'] }}</a>
                    @endif
                    @if ($site['contact_email'])
                        <a href="mailto:{{ $site['contact_email'] }}" class="block hover:text-white hover:underline">{{ $site['contact_email'] }}</a>
                    @endif
                </p>
                <a href="{{ route('site.contact') }}" class="mt-4 inline-flex items-center gap-1.5 font-semibold text-seal-300 hover:text-seal-100 hover:underline">
                    All Contact Information <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
            </div>
        </div>
    </x-site.section>
</x-layouts.public>
