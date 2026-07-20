@props(['items' => []])
{{-- Vertical stacked-pill menu for the active nav group. Styling is plain CSS
     in public/css/municipal.css so nothing can purge it. --}}
@if (count($items))
    <nav class="st-menu" aria-label="Section Menu">
        @foreach ($items as [$label, $href, $icon, $active])
            <a href="{{ $href }}" class="st-item {{ $active ? 'is-active' : '' }}" @if ($active) aria-current="page" @endif>
                <x-icon :name="$icon" />
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>
@endif
