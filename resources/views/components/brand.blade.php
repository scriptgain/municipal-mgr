@props(['href' => null, 'label' => null, 'sub' => null])
{{-- Wordmark. No chip or white box behind the logo (house style). The mark
     inherits the bar's text colour; the glyph keeps the seal-gold accent. --}}
<a href="{{ $href ?? url('/') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 font-semibold tracking-tight']) }}>
    <x-icon :name="config('brand.icon', 'building')" class="w-7 h-7 text-seal-500 shrink-0" />
    <span class="leading-tight">
        <span class="block text-lg">{{ $label ?? config('brand.name') }}</span>
        @if ($sub)<span class="block text-[11px] font-medium uppercase tracking-widest opacity-70">{{ $sub }}</span>@endif
    </span>
</a>
