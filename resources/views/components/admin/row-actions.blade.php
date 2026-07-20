@props(['edit', 'delete' => null, 'preview' => null, 'name', 'title' => 'Delete This Record?', 'message' => 'This permanently removes the record from the public site.'])
<div class="flex items-center justify-end gap-1">
    @if ($preview)
        <a href="{{ $preview }}" target="_blank" rel="noopener" data-tip="Preview On The Public Site" aria-label="Preview"
           class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-brand-700 transition">
            <x-icon name="eye" class="w-4 h-4" />
        </a>
    @endif
    <a href="{{ $edit }}" data-tip="Edit" aria-label="Edit"
       class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-brand-700 transition">
        <x-icon name="edit" class="w-4 h-4" />
    </a>
    @if ($delete)
        <x-delete-button :name="$name" :action="$delete" :title="$title" :message="$message" />
    @endif
    {{ $slot }}
</div>
