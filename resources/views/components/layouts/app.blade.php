@props(['title' => null, 'shellMaxWidth' => config('municipal.max_width', 'max-w-7xl')])
{{-- Staff panel layout. Markup only: navigation, breadcrumbs, badge counts and
     the active section menu all arrive from AdminLayoutComposer. --}}
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title . ' — ' . config('brand.name') : config('brand.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ route('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="64x64" href="{{ route('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ route('favicon.apple') }}">
    <x-tailwind-cdn />
    <x-accent-style />
</head>
<body class="h-full min-h-full bg-slate-50">
<a href="#main-content" class="skip-link">Skip To Main Content</a>
<x-demo-banner />

<div class="min-h-full flex flex-col">
    {{-- Brand accent hairline --}}
    <div class="h-0.5 bg-gradient-to-r from-brand-700 via-seal-500 to-brand-700"></div>

    {{-- Dark top utility bar --}}
    <div class="bg-chrome text-slate-300 text-sm ring-1 ring-inset ring-white/5">
        <div class="{{ $shellMaxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-12 items-center justify-between gap-4">
                <x-brand class="text-white" :sub="'Staff Panel'" :href="route('dashboard')" />

                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="{{ route('site.home') }}" target="_blank" rel="noopener"
                       class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white ring-1 ring-inset ring-white/10 hover:bg-white/20 transition"
                       data-tip="Open the live public website in a new tab">
                        <x-icon name="globe" class="w-3.5 h-3.5" /> View Public Site
                    </a>

                    @if ($openRequestCount)
                        <a href="{{ route('service-requests.index') }}"
                           class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-amber-400/10 px-2.5 py-1 text-xs font-medium text-amber-200 ring-1 ring-inset ring-amber-400/20 hover:bg-amber-400/20 transition">
                            <x-icon name="bolt" class="w-3.5 h-3.5" /> {{ $openRequestCount }} Open
                        </a>
                    @endif

                    <span class="hidden sm:inline-block h-5 w-px bg-white/10"></span>

                    <x-dropdown align="right">
                        <x-slot:trigger>
                            <button class="inline-flex items-center gap-2 rounded-full py-1 pl-1 pr-2 hover:bg-white/10 transition">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-brand-500/25 text-brand-100 text-xs font-semibold ring-1 ring-brand-300/40">{{ $userInitials }}</span>
                                <span class="hidden sm:block text-xs font-medium text-slate-200 max-w-[9rem] truncate">{{ $userName }}</span>
                                <x-icon name="chevron-down" class="w-4 h-4 text-slate-400" />
                            </button>
                        </x-slot:trigger>
                        <div class="px-3 py-2.5 border-b border-slate-100">
                            <p class="text-sm font-medium text-slate-900 truncate">{{ $userName }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $userEmail }}</p>
                            <span class="mt-1.5 inline-flex items-center rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-700 ring-1 ring-inset ring-brand-200">{{ $userRoleLabel }}</span>
                        </div>
                        <x-dropdown-item icon="settings" href="{{ route('settings.index') }}">Settings</x-dropdown-item>
                        <x-dropdown-item icon="building" href="{{ route('settings.site.edit') }}">Site Identity</x-dropdown-item>
                        @if ($userIsAdmin)
                            <x-dropdown-item icon="users" href="{{ route('settings.users.index') }}">Users And Roles</x-dropdown-item>
                            <x-dropdown-item icon="book" href="{{ route('settings.audit.index') }}">Audit Log</x-dropdown-item>
                        @endif
                        <div class="my-1 border-t border-slate-100"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-left text-rose-600 hover:bg-rose-50">
                                <x-icon name="x-circle" class="w-4 h-4 shrink-0" /> Sign Out
                            </button>
                        </form>
                    </x-dropdown>
                </div>
            </div>
        </div>
    </div>

    {{-- Main navbar, separate from the utility bar --}}
    <header x-data="{ mobileOpen: false }" class="bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80 border-b border-slate-200 sticky top-0 z-30">
        <div class="{{ $shellMaxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-14 items-center justify-between gap-3">
                <div class="flex items-center gap-1 min-w-0">
                    <button type="button" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen.toString()" aria-label="Toggle Menu"
                            class="lg:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-600 hover:bg-slate-100 transition shrink-0">
                        <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" /></svg>
                        <svg x-show="mobileOpen" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>

                    <nav class="hidden lg:flex items-center gap-1" aria-label="Primary">
                        @foreach ($navGroups as $item)
                            @if ($item['type'] === 'link')
                                <x-nav-link :href="$item['href']" :icon="$item['icon']" :active="$item['active']">{{ $item['label'] }}</x-nav-link>
                            @else
                                <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
                                    <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                                            @class([
                                                'inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition ring-1 ring-inset',
                                                'text-brand-700 bg-brand-50 ring-brand-200' => $item['active'],
                                                'text-slate-600 ring-transparent hover:text-slate-900 hover:bg-slate-100 hover:ring-slate-200' => ! $item['active'],
                                            ])>
                                        <x-icon :name="$item['icon']" class="w-4 h-4 shrink-0" />
                                        {{ $item['label'] }}
                                        <x-icon name="chevron-down" class="w-4 h-4 -mr-0.5 text-slate-400 transition-transform" ::class="open && 'rotate-180'" />
                                    </button>
                                    <div x-show="open" x-cloak x-transition @click="open = false"
                                         class="absolute left-0 z-40 mt-2 w-64 origin-top-left rounded-lg bg-white shadow-lg ring-1 ring-slate-200 py-1">
                                        @foreach ($item['items'] as [$label, $href, $icon, $active])
                                            <a href="{{ $href }}" @class([
                                                'flex items-center gap-2.5 px-3 py-2 text-sm transition',
                                                'text-brand-700 bg-brand-50 font-medium' => $active,
                                                'text-slate-700 hover:bg-slate-100' => ! $active,
                                            ])>
                                                <x-icon :name="$icon" class="w-4 h-4 shrink-0 {{ $active ? 'text-brand-600' : 'text-slate-400' }}" /> {{ $label }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </nav>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <x-button href="{{ route('news.create') }}" icon="plus" size="sm">
                        <span class="hidden sm:inline">Post An Announcement</span><span class="sm:hidden">Post</span>
                    </x-button>
                </div>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div x-show="mobileOpen" x-cloak
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="lg:hidden border-t border-slate-100 bg-white shadow-sm">
            <nav class="{{ $shellMaxWidth }} mx-auto px-4 sm:px-6 py-3 space-y-3" aria-label="Primary Mobile">
                @foreach ($navGroups as $item)
                    @if ($item['type'] === 'link')
                        <a href="{{ $item['href'] }}" @class([
                            'flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                            'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200' => $item['active'],
                            'text-slate-600 hover:bg-slate-100' => ! $item['active'],
                        ])>
                            <x-icon :name="$item['icon']" class="w-4 h-4 shrink-0" /> {{ $item['label'] }}
                        </a>
                    @else
                        <div>
                            <p class="px-3 pb-1.5 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $item['label'] }}</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                @foreach ($item['items'] as [$label, $href, $icon, $active])
                                    <a href="{{ $href }}" @class([
                                        'flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                                        'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200' => $active,
                                        'text-slate-600 hover:bg-slate-100' => ! $active,
                                    ])>
                                        <x-icon :name="$icon" class="w-4 h-4 shrink-0" /> {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>
        </div>
    </header>

    {{-- Breadcrumbs --}}
    @if (count($crumbs))
        <div class="bg-white border-b border-slate-200">
            <div class="{{ $shellMaxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex items-center gap-2 h-10 text-sm" aria-label="Breadcrumb">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-slate-500 hover:text-brand-700 transition">
                        <x-icon name="home" class="w-4 h-4" /> Dashboard
                    </a>
                    @foreach ($crumbs as $crumb)
                        <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 shrink-0" />
                        @if ($crumb['href'])
                            <a href="{{ $crumb['href'] }}" class="text-slate-500 hover:text-brand-700 transition">{{ $crumb['label'] }}</a>
                        @else
                            <span class="font-medium text-slate-900 truncate max-w-[20rem]">{{ $crumb['label'] }}</span>
                        @endif
                    @endforeach
                </nav>
            </div>
        </div>
    @endif

    <main id="main-content" class="flex-1 py-8">
        <div class="{{ $shellMaxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
            <x-license-banner class="mb-6" />
            <x-update-banner />

            @if (session('status'))
                <div class="mb-6"><x-alert type="success">{{ session('status') }}</x-alert></div>
            @endif
            @if (session('warning'))
                <div class="mb-6"><x-alert type="warn">{{ session('warning') }}</x-alert></div>
            @endif
            @if ($errors->any())
                <div class="mb-6"><x-alert type="danger">Please Correct The Highlighted Fields Below.</x-alert></div>
            @endif

            @if (request()->routeIs('settings.*'))
                <div class="settings-shell">
                    <aside class="settings-aside"><x-settings-tabs /></aside>
                    <div>{{ $slot }}</div>
                </div>
            @elseif ($activeGroupItems)
                <div class="settings-shell">
                    <aside class="settings-aside"><x-side-menu :items="$activeGroupItems" /></aside>
                    <div>{{ $slot }}</div>
                </div>
            @else
                {{ $slot }}
            @endif
        </div>
    </main>

    <footer class="section-divider bg-white">
        <div class="{{ $shellMaxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
            <span>{{ config('brand.name') }} &middot; {{ config('brand.tagline') }}</span>
            <span class="tabular">v{{ \App\Services\UpdateService::currentVersion() }}</span>
        </div>
    </footer>
</div>
</body>
</html>
