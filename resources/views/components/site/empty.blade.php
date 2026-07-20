@props(['title' => 'Nothing Posted Yet', 'message' => null, 'icon' => 'folder'])
<div class="rounded-2xl bg-slate-50 px-6 py-16 text-center ring-1 ring-slate-200">
    <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-brand-600 ring-1 ring-brand-100">
        <x-icon :name="$icon" class="w-7 h-7" />
    </span>
    <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ $title }}</h3>
    @if ($message)<p class="mx-auto mt-2 max-w-lg text-slate-600">{{ $message }}</p>@endif
</div>
