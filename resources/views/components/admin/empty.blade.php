@props(['title' => 'Nothing Here Yet', 'message' => null, 'icon' => 'folder', 'href' => null, 'label' => 'Add The First One'])
<div class="px-6 py-16 text-center">
    <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-brand-100">
        <x-icon :name="$icon" class="w-6 h-6" />
    </span>
    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $title }}</h3>
    @if ($message)<p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ $message }}</p>@endif
    @if ($href)<div class="mt-5"><x-button :href="$href" icon="plus" size="sm">{{ $label }}</x-button></div>@endif
</div>
