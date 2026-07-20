@props(['title' => null, 'description' => null, 'heroless' => false, 'maxWidth' => config('municipal.max_width', 'max-w-7xl')])
{{-- Public municipal site layout. Semantic landmarks, a skip link, visible
     focus states, and a real <h1> per page — accessibility is a legal
     expectation for a government site, not a polish item. Every variable here
     comes from PublicLayoutComposer; this file is markup only. --}}
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title . ' | ' . $siteName : $siteFormalName }}</title>
    <meta name="description" content="{{ $description ?? ($site['site_motto'] ?: 'Official website of ' . $siteFormalName) }}">
    <meta property="og:title" content="{{ $title ?? $siteName }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:type" content="website">
    <link rel="icon" type="image/svg+xml" href="{{ route('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ route('favicon.apple') }}">
    <x-tailwind-cdn />
    <x-accent-style />
</head>
<body class="h-full min-h-full bg-white text-slate-800">
<a href="#main-content" class="skip-link">Skip To Main Content</a>

<div class="min-h-full flex flex-col">

    {{-- Emergency / advisory banner --}}
    @if ($liveAlert)
        <div data-alert-id="{{ $liveAlert['id'] }}" role="{{ $liveAlert['level'] === 'emergency' ? 'alert' : 'status' }}"
             @class([
                'border-b',
                'bg-rose-700 text-white border-rose-800' => $liveAlert['level'] === 'emergency',
                'bg-amber-500 text-amber-950 border-amber-600' => $liveAlert['level'] === 'warning',
                'bg-seal-100 text-brand-900 border-seal-300' => $liveAlert['level'] === 'advisory',
                'bg-brand-50 text-brand-900 border-brand-200' => $liveAlert['level'] === 'info',
             ])>
            <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-start gap-3">
                <x-icon name="warning" class="w-5 h-5 shrink-0 mt-0.5" />
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">{{ $liveAlert['title'] }}</p>
                    @if ($liveAlert['message'])<p class="mt-0.5 text-sm opacity-90">{{ $liveAlert['message'] }}</p>@endif
                    @if ($liveAlert['link_url'])
                        <a href="{{ $liveAlert['link_url'] }}" class="mt-1 inline-flex items-center gap-1 text-sm font-semibold underline underline-offset-2">
                            {{ $liveAlert['link_label'] ?: 'Read More' }} <x-icon name="chevron-right" class="w-4 h-4" />
                        </a>
                    @endif
                </div>
                @if ($liveAlert['dismissible'])
                    <button type="button" data-alert-dismiss aria-label="Dismiss This Alert"
                            class="shrink-0 rounded-lg p-1 hover:bg-black/10 transition">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- Dark top utility bar --}}
    <div class="bg-chrome text-slate-300 text-sm">
        <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-11 items-center justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    @if ($site['contact_phone'])
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact_phone']) }}" class="inline-flex items-center gap-1.5 hover:text-white transition">
                            <x-icon name="phone" class="w-3.5 h-3.5 text-seal-500" /> <span class="hidden sm:inline">{{ $site['contact_phone'] }}</span>
                        </a>
                    @endif
                    @if ($site['contact_hours'])
                        <span class="hidden md:inline-flex items-center gap-1.5 text-slate-400">
                            <x-icon name="clock" class="w-3.5 h-3.5 text-seal-500" /> {{ $site['contact_hours'] }}
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    @foreach ($utilityNav as $item)
                        <a href="{{ $item['href'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif
                           class="hidden sm:inline text-xs font-medium hover:text-white transition">{{ $item['label'] }}</a>
                    @endforeach
                    <a href="{{ route('site.track') }}" class="text-xs font-medium hover:text-white transition">Track A Request</a>
                    <a href="{{ route('site.search') }}" class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white ring-1 ring-inset ring-white/10 hover:bg-white/20 transition">
                        <x-icon name="search" class="w-3.5 h-3.5" /> Search
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Main navbar --}}
    <header x-data="{ mobileOpen: false }" class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-20 items-center justify-between gap-4">
                {{-- Wordmark: no white chip or box behind it --}}
                <a href="{{ route('site.home') }}" class="flex shrink items-center gap-3 min-w-0">
                    @if ($site['site_seal_path'])
                        <img src="{{ municipal_upload_url($site['site_seal_path']) }}" alt="Official seal of {{ $siteName }}" class="h-10 w-10 object-contain shrink-0">
                    @else
                        <x-icon name="building" class="h-8 w-8 text-brand-600 shrink-0" />
                    @endif
                    <span class="min-w-0">
                        <span class="block font-display text-base sm:text-lg font-semibold leading-tight tracking-tight text-brand-800 truncate">{{ $siteName }}</span>
                        @if ($site['site_state'])
                            <span class="block text-[10px] font-semibold uppercase tracking-[0.16em] text-seal-700">{{ $site['site_state'] }}</span>
                        @endif
                    </span>
                </a>

                <nav class="hidden lg:flex items-center gap-1" aria-label="Primary">
                    <a href="{{ route('site.home') }}"
                       @if (request()->routeIs('site.home')) aria-current="page" @endif
                       class="group inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg px-3 py-2 text-[15px] font-medium transition {{ request()->routeIs('site.home') ? 'bg-brand-50 text-brand-800' : 'text-slate-700 hover:bg-brand-50 hover:text-brand-800' }}">
                        <x-icon name="home" class="w-4 h-4 {{ request()->routeIs('site.home') ? 'text-brand-700' : 'text-slate-400 group-hover:text-brand-600' }} transition-colors" />
                        Home
                    </a>
                    @foreach ($primaryNav as $item)
                        @if (count($item['children']))
                            <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
                                <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                                        class="group inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg px-3 py-2 text-[15px] font-medium transition {{ $item['is_active'] ? 'bg-brand-50 text-brand-800' : 'text-slate-700 hover:bg-brand-50 hover:text-brand-800' }}">
                                    @if ($item['icon'])
                                        <x-icon :name="$item['icon']" class="w-4 h-4 {{ $item['is_active'] ? 'text-brand-700' : 'text-slate-400 group-hover:text-brand-600' }} transition-colors" />
                                    @endif
                                    {{ $item['label'] }}
                                    <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 transition-transform" ::class="open && 'rotate-180'" />
                                </button>
                                <div x-show="open" x-cloak x-transition @click="open = false"
                                     class="absolute left-0 z-40 mt-1 w-72 rounded-xl bg-white p-2 shadow-xl ring-1 ring-slate-200">
                                    @foreach ($item['children'] as $child)
                                        <a href="{{ $child['href'] }}" @if ($child['new_tab']) target="_blank" rel="noopener" @endif
                                           class="flex items-start gap-3 rounded-lg px-3 py-2.5 hover:bg-brand-50 transition">
                                            @if ($child['icon'])
                                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 ring-1 ring-brand-200">
                                                    <x-icon :name="$child['icon']" class="w-4 h-4" />
                                                </span>
                                            @endif
                                            <span class="min-w-0">
                                                <span class="block text-sm font-medium text-slate-900">{{ $child['label'] }}</span>
                                                @if ($child['description'])<span class="block text-xs text-slate-500">{{ $child['description'] }}</span>@endif
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ $item['href'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif
                               @if ($item['is_active']) aria-current="page" @endif
                               class="group inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg px-3 py-2 text-[15px] font-medium transition {{ $item['is_active'] ? 'bg-brand-50 text-brand-800' : 'text-slate-700 hover:bg-brand-50 hover:text-brand-800' }}">
                                @if ($item['icon'])
                                    <x-icon :name="$item['icon']" class="w-4 h-4 {{ $item['is_active'] ? 'text-brand-700' : 'text-slate-400 group-hover:text-brand-600' }} transition-colors" />
                                @endif
                                {{ $item['label'] }}
                            </a>
                        @endif
                    @endforeach
                    <a href="{{ route('site.report') }}"
                       class="ml-2 inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-800 transition">
                        <x-icon name="bolt" class="w-4 h-4 shrink-0" /> Report An Issue
                    </a>
                </nav>

                <button type="button" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen.toString()" aria-label="Toggle Menu"
                        class="lg:hidden inline-flex items-center justify-center w-11 h-11 rounded-lg text-slate-700 hover:bg-slate-100 transition shrink-0">
                    <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" /></svg>
                    <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>

        <div x-show="mobileOpen" x-cloak x-transition class="lg:hidden border-t border-slate-100 bg-white">
            <nav class="{{ $maxWidth }} mx-auto px-4 sm:px-6 py-4 space-y-1" aria-label="Primary Mobile">
                <a href="{{ route('site.home') }}"
                   @if (request()->routeIs('site.home')) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-lg px-3 py-3 text-base font-medium transition {{ request()->routeIs('site.home') ? 'bg-brand-50 text-brand-800' : 'text-slate-800 hover:bg-brand-50' }}">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 ring-1 ring-brand-200">
                        <x-icon name="home" class="w-4 h-4" />
                    </span>
                    Home
                </a>
                @foreach ($primaryNav as $item)
                    <a href="{{ $item['href'] }}"
                       @if ($item['is_active']) aria-current="page" @endif
                       class="flex items-center gap-3 rounded-lg px-3 py-3 text-base font-medium transition {{ $item['is_active'] ? 'bg-brand-50 text-brand-800' : 'text-slate-800 hover:bg-brand-50' }}">
                        @if ($item['icon'])
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 ring-1 ring-brand-200">
                                <x-icon :name="$item['icon']" class="w-4 h-4" />
                            </span>
                        @endif
                        {{ $item['label'] }}
                    </a>
                    @foreach ($item['children'] as $child)
                        <a href="{{ $child['href'] }}" class="flex items-center gap-2.5 rounded-lg py-2.5 pl-11 pr-3 text-sm text-slate-600 hover:bg-brand-50 transition">
                            @if ($child['icon'])
                                <x-icon :name="$child['icon']" class="w-4 h-4 shrink-0 text-slate-400" />
                            @endif
                            {{ $child['label'] }}
                        </a>
                    @endforeach
                @endforeach
                <a href="{{ route('site.report') }}" class="mt-2 flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-3 text-sm font-semibold text-white">
                    <x-icon name="bolt" class="w-4 h-4" /> Report An Issue
                </a>
            </nav>
        </div>
    </header>

    <main id="main-content" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="site-footer relative isolate mt-16 overflow-hidden bg-brand-950 text-slate-300">
        <div class="site-footer-wash absolute inset-0 -z-10"></div>
        <div class="site-hero-pattern absolute inset-0 -z-10" aria-hidden="true"></div>
        {{-- Gold hairline hands the page body off to the footer --}}
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-seal-500/50 to-transparent"></div>

        {{-- Contact strip: the three things residents look for first --}}
        <div class="relative border-b border-white/10">
            <div class="{{ $maxWidth }} mx-auto grid gap-px px-4 sm:px-6 lg:px-8 sm:grid-cols-2 lg:grid-cols-3">
                @if ($site['contact_phone'])
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact_phone']) }}"
                       class="group flex items-center gap-4 py-6 transition hover:bg-white/5 lg:px-6">
                        <span class="min-w-0">
                            <span class="block text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Call The Town</span>
                            <span class="block truncate font-medium text-white">{{ $site['contact_phone'] }}</span>
                        </span>
                    </a>
                @endif
                @if ($site['contact_email'])
                    <a href="mailto:{{ $site['contact_email'] }}"
                       class="group flex items-center gap-4 py-6 transition hover:bg-white/5 lg:px-6">
                        <span class="min-w-0">
                            <span class="block text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Email Us</span>
                            <span class="block truncate font-medium text-white">{{ $site['contact_email'] }}</span>
                        </span>
                    </a>
                @endif
                <a href="{{ route('site.report') }}"
                   class="group flex items-center gap-4 py-6 transition hover:bg-white/5 lg:px-6">
                    <span class="min-w-0">
                        <span class="block text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Something Broken?</span>
                        <span class="block truncate font-medium text-white">Report An Issue</span>
                    </span>
                </a>
            </div>
        </div>

        <div class="relative {{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-14">
            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-4">
                <div class="lg:pr-6">
                    <div class="flex items-center gap-3">
                        @if ($site['site_seal_path'])
                            <img src="{{ municipal_upload_url($site['site_seal_path']) }}" alt="" class="h-12 w-12 object-contain">
                        @else
                            <x-icon name="building" class="h-9 w-9 text-seal-500" />
                        @endif
                        <span class="min-w-0">
                            <span class="block font-display text-lg font-semibold leading-tight text-white">{{ $siteName }}</span>
                            @if ($site['site_state'])
                                <span class="block text-[10px] font-semibold uppercase tracking-[0.16em] text-seal-500">{{ $site['site_state'] }}</span>
                            @endif
                        </span>
                    </div>
                    <span class="seal-rule mt-5"></span>
                    <address class="mt-5 not-italic text-sm leading-relaxed text-slate-400">
                        @if ($site['contact_address'])<span class="block">{{ $site['contact_address'] }}</span>@endif
                        @if ($site['contact_city_state_zip'])<span class="block">{{ $site['contact_city_state_zip'] }}</span>@endif
                    </address>
                    @if ($site['contact_hours'])
                        <p class="mt-4 flex items-start gap-2 text-sm text-slate-400">
                            <span>{{ $site['contact_hours'] }}</span>
                        </p>
                    @endif
                </div>

                <div>
                    <h2 class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white">Departments</h2>
                    <span class="seal-rule mt-3 w-8"></span>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        @foreach ($footerDepartments as $department)
                            <li>
                                <a href="{{ $department['href'] }}" class="text-slate-400 transition hover:text-white">
                                    {{ $department['name'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h2 class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white">Resources</h2>
                    <span class="seal-rule mt-3 w-8"></span>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        @foreach ($footerNav as $item)
                            <li>
                                <a href="{{ $item['href'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif
                                   class="text-slate-400 transition hover:text-white">
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h2 class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white">Stay Connected</h2>
                    <span class="seal-rule mt-3 w-8"></span>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach (['social_facebook' => 'Facebook', 'social_x' => 'X', 'social_youtube' => 'YouTube', 'social_instagram' => 'Instagram', 'social_nextdoor' => 'Nextdoor'] as $key => $network)
                            @if ($site[$key])
                                <a href="{{ $site[$key] }}" target="_blank" rel="noopener"
                                   class="rounded-lg bg-white/10 px-3 py-2 text-xs font-medium text-white ring-1 ring-inset ring-white/15 transition hover:bg-white/20 hover:ring-white/30">{{ $network }}</a>
                            @endif
                        @endforeach
                    </div>
                    @if ($site['contact_after_hours'])
                        <div class="mt-5 rounded-xl bg-white/5 p-4 ring-1 ring-inset ring-white/10">
                            <p class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-seal-300">
                                After Hours
                            </p>
                            <p class="mt-2 text-xs leading-relaxed text-slate-400">{{ $site['contact_after_hours'] }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-12 border-t border-white/10 pt-6 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-400">
                <p>&copy; {{ $currentYear }} {{ $siteFormalName }}. {{ $site['footer_note'] }}</p>
                <p class="flex flex-wrap items-center gap-x-5 gap-y-2">
                    <a href="{{ route('site.accessibility') }}" class="transition hover:text-white">Accessibility</a>
                    <a href="{{ route('site.contact') }}" class="transition hover:text-white">Contact</a>
                    <a href="{{ route('site.search') }}" class="transition hover:text-white">Search</a>
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-white/5 px-2.5 py-1 ring-1 ring-inset ring-white/10 transition hover:bg-white/10 hover:text-white">
                        Staff Login
                    </a>
                </p>
            </div>
        </div>
    </footer>
</div>
</body>
</html>
