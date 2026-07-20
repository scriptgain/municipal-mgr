@props(['title', 'subtitle' => null, 'eyebrow' => null, 'crumbs' => [], 'maxWidth' => config('municipal.max_width', 'max-w-7xl')])
{{-- Standard interior page banner: navy field, gold rule, breadcrumb trail. --}}
<section class="bg-brand-800 text-white">
    <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        @if (count($crumbs))
            <nav aria-label="Breadcrumb" class="mb-4">
                <ol class="flex flex-wrap items-center gap-2 text-sm text-brand-100">
                    <li><a href="{{ route('site.home') }}" class="hover:text-white hover:underline">Home</a></li>
                    @foreach ($crumbs as $crumb)
                        <li aria-hidden="true" class="text-brand-300">/</li>
                        <li>
                            @if (! empty($crumb['href']))
                                <a href="{{ $crumb['href'] }}" class="hover:text-white hover:underline">{{ $crumb['label'] }}</a>
                            @else
                                <span class="text-white" aria-current="page">{{ $crumb['label'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        @if ($eyebrow)
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-seal-300">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-2 font-display text-3xl sm:text-4xl font-semibold tracking-tight">{{ $title }}</h1>
        <span class="seal-rule mt-4"></span>
        @if ($subtitle)
            <p class="mt-4 max-w-3xl text-lg text-brand-100">{{ $subtitle }}</p>
        @endif
    </div>
</section>
