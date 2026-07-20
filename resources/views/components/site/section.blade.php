@props(['title' => null, 'subtitle' => null, 'href' => null, 'linkLabel' => 'View All', 'tone' => 'white', 'divider' => true, 'maxWidth' => config('municipal.max_width', 'max-w-7xl')])
{{-- A full-width page section. Width comes from the configured $maxWidth,
     never a hardcoded max-w-*, and sections are separated by a visible
     divider (house style). --}}
<section @class([
    'py-12 sm:py-16',
    'bg-white' => $tone === 'white',
    'bg-slate-50' => $tone === 'muted',
    'bg-brand-800 text-white' => $tone === 'navy',
    'section-divider' => $divider && $tone !== 'navy',
])>
    <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
        @if ($title)
            <x-section-heading :title="$title" :subtitle="$subtitle" :href="$href" :linkLabel="$linkLabel" />
        @endif
        {{ $slot }}
    </div>
</section>
