@props(['href', 'title', 'description' => null, 'icon' => null, 'meta' => null, 'newTab' => false])
<a href="{{ $href }}" @if ($newTab) target="_blank" rel="noopener" @endif
   {{ $attributes->merge(['class' => 'group flex h-full flex-col rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md hover:ring-brand-300']) }}>
    @if ($icon)
        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
            <x-icon :name="$icon" class="w-6 h-6" />
        </span>
    @endif
    @if ($meta)<p class="mt-4 text-xs font-semibold uppercase tracking-wide text-seal-700">{{ $meta }}</p>@endif
    <h3 class="{{ $icon ? 'mt-4' : '' }} text-lg font-semibold text-slate-900 group-hover:text-brand-800">{{ $title }}</h3>
    @if ($description)<p class="mt-2 flex-1 text-sm leading-relaxed text-slate-600">{{ $description }}</p>@endif
    <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700">
        Learn More <x-icon name="chevron-right" class="w-4 h-4 transition-transform group-hover:translate-x-0.5" />
    </span>
</a>
