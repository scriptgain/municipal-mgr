{{-- Wordmark. No chip or white box behind the logo (house style). The glyph
     inherits the bar's text colour and keeps the seal accent; an active theme
     may replace it with an uploaded logo. Everything is resolved in
     App\View\Components\Brand, so this file is markup only. --}}
<a href="{{ $href ?? url('/') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 font-semibold tracking-tight']) }}>
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="" class="h-7 w-auto max-w-[10rem] shrink-0 object-contain">
    @else
        <x-icon :name="$icon" class="w-7 h-7 text-seal-500 shrink-0" />
    @endif
    <span class="leading-tight">
        <span class="block text-lg">{{ $label ?? config('brand.name') }}</span>
        @if ($sub)<span class="block text-[11px] font-medium uppercase tracking-widest opacity-70">{{ $sub }}</span>@endif
    </span>
</a>
