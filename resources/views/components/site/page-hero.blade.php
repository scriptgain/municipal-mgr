@props(['title', 'subtitle' => null, 'eyebrow' => null, 'icon' => null, 'crumbs' => [], 'maxWidth' => config('municipal.max_width', 'max-w-7xl')])
{{-- Interior page banner. Shares the home hero's gradient and contour pattern
     so inner pages read as the same site, at a shorter height. --}}
<section class="site-pagehead relative isolate overflow-hidden bg-brand-900 text-white">
    <div class="site-pagehead-wash absolute inset-0 -z-10"></div>

    <div class="relative {{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-9 sm:py-12">
        @if (count($crumbs))
            <nav aria-label="Breadcrumb" class="mb-6">
                <ol class="flex flex-wrap items-center gap-1 text-sm">
                    <li>
                        <a href="{{ route('site.home') }}"
                           class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-brand-200 transition hover:bg-white/10 hover:text-white">
                            <x-icon name="home" class="w-3.5 h-3.5 shrink-0" />
                            <span class="sr-only sm:not-sr-only">Home</span>
                        </a>
                    </li>
                    @foreach ($crumbs as $crumb)
                        <li aria-hidden="true" class="text-brand-400">
                            <x-icon name="chevron-right" class="w-3.5 h-3.5" />
                        </li>
                        <li>
                            @if (! empty($crumb['href']))
                                <a href="{{ $crumb['href'] }}"
                                   class="inline-block rounded-lg px-2 py-1 text-brand-200 transition hover:bg-white/10 hover:text-white">{{ $crumb['label'] }}</a>
                            @else
                                <span aria-current="page"
                                      class="inline-block rounded-lg bg-white/10 px-2.5 py-1 font-medium text-white ring-1 ring-inset ring-white/15">{{ $crumb['label'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <div class="flex items-start gap-4 sm:gap-5">
            @if ($icon)
                <span class="mt-1 inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-seal-300 ring-1 ring-inset ring-white/20 backdrop-blur sm:h-14 sm:w-14">
                    <x-icon :name="$icon" class="w-6 h-6 sm:w-7 sm:h-7" />
                </span>
            @endif
            <div class="min-w-0">
                @if ($eyebrow)
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-seal-300">{{ $eyebrow }}</p>
                @endif
                <h1 class="{{ $eyebrow ? 'mt-2' : '' }} font-display text-3xl sm:text-4xl font-semibold leading-tight tracking-tight">{{ $title }}</h1>
                <span class="seal-rule mt-4"></span>
                @if ($subtitle)
                    <p class="mt-4 max-w-3xl text-lg leading-relaxed text-brand-100">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Hairline that hands off to the page body --}}
    <div class="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-seal-500/40 to-transparent"></div>
</section>
