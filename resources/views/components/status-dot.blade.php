@props(['color' => 'neutral', 'pulse' => false, 'label' => null])
@php
    $map = [
        'neutral' => 'bg-slate-400',
        'info' => 'bg-brand-500',
        'success' => 'bg-emerald-500',
        'warn' => 'bg-amber-500',
        'danger' => 'bg-rose-500',
    ];
    $dot = $map[$color] ?? $map['neutral'];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <span class="relative flex h-2 w-2 shrink-0">
        @if ($pulse)<span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-70 {{ $dot }}"></span>@endif
        <span class="relative inline-flex h-2 w-2 rounded-full {{ $dot }}"></span>
    </span>
    @if ($label)<span>{{ $label }}</span>@endif
</span>
