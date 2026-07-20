@props(['initials' => '?', 'src' => null, 'name' => null, 'size' => 'md'])
@php
    $sizes = ['sm' => 'w-8 h-8 text-xs', 'md' => 'w-10 h-10 text-sm', 'lg' => 'w-14 h-14 text-base', 'xl' => 'w-24 h-24 text-2xl'];
    $box = $sizes[$size] ?? $sizes['md'];
@endphp
@if ($src)
    <img src="{{ $src }}" alt="{{ $name ? 'Photograph of ' . $name : '' }}"
         {{ $attributes->merge(['class' => "$box rounded-full object-cover ring-1 ring-slate-200 shrink-0"]) }}>
@else
    <span aria-hidden="true"
          {{ $attributes->merge(['class' => "$box inline-flex items-center justify-center rounded-full bg-brand-50 font-semibold text-brand-700 ring-1 ring-brand-200 shrink-0"]) }}>{{ $initials }}</span>
@endif
