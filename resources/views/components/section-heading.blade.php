@props(['title', 'subtitle' => null, 'href' => null, 'linkLabel' => 'View All'])
{{-- Civic section heading: eyebrow rule in seal gold, title, optional link. --}}
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-4 pb-5']) }}>
    <div class="min-w-0">
        <span class="seal-rule mb-3"></span>
        <h2 class="font-display text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">{{ $title }}</h2>
        @if ($subtitle)<p class="mt-1.5 text-slate-600">{{ $subtitle }}</p>@endif
    </div>
    @if ($href)
        <a href="{{ $href }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50 hover:text-brand-800 transition">
            {{ $linkLabel }} <x-icon name="chevron-right" class="w-4 h-4" />
        </a>
    @endif
</div>
